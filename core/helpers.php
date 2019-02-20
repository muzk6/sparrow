<?php

use Core\AppAuth;
use Core\AppCSRF;
use Core\AppEmail;
use Core\AppException;
use Core\AppFlash;
use Core\AppAes;
use Core\AppPDO;
use Core\AppQueue;
use Core\AppWhitelist;

/**
 * 配置文件
 * @param string $filename 无后缀的文件名
 * @return array|null 返回配置文件内容
 */
function config(string $filename)
{
    if (is_file($path = PATH_CONFIG . "/{$filename}.php")) {
        return include($path);
    } else if (is_file($path = PATH_CONFIG_ENV . "/{$filename}.php")) {
        return include($path);
    }

    return null;
}

/**
 * 多语言文本
 * @param int $code
 * @param array $params
 * @return string
 */
function trans(int $code, array $params = [])
{
    $text = '?';
    $lang = include(sprintf('%s/%s.php', PATH_LANG, APP_LANG));
    if (isset($lang[$code])) {
        $text = $lang[$code];
    } else { // 不存在就取默认语言的文本
        $conf = config('app');
        if ($conf['lang'] != APP_LANG) {
            $lang = include(sprintf('%s/%s.php', PATH_LANG, $conf['lang']));
            $text = $lang[$code] ?? '?';
        }
    }

    if ($params) {
        foreach ($params as $k => $v) {
            $text = str_replace("{{$k}}", $v, $text);
        }
    }

    return $text;
}

/**
 * 视图模板
 * @return \duncan3dc\Laravel\BladeInstance
 * @throws null
 */
function view()
{
    static $blade = null;

    if (!$blade) {
        if (!class_exists('\duncan3dc\Laravel\BladeInstance')) {
            throw new AppException('composer require duncan3dc/blade');
        }

        $blade = new \duncan3dc\Laravel\BladeInstance(PATH_VIEW, PATH_DATA . '/view_cache');
    }

    return $blade;
}

/**
 * 数据库 pdo
 */
function db()
{
    static $pdo = null;

    if (!$pdo) {
        $pdo = AppPDO::instance(config('database'));
    }

    return $pdo;
}

/**
 * 文件日志
 * @param array|string $data 日志内容
 * @param string $type 日志类型，用于区分日志文件
 * @return false|int
 */
function logfile($data, string $type = 'app')
{
    $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1)[0];
    $type = trim(str_replace('/', '', $type));

    $log = json_encode([
        '__time' => date('Y-m-d H:i:s'),
        '__requestid' => isset($_SERVER['REQUEST_TIME_FLOAT']) ? md5(strval($_SERVER['REQUEST_TIME_FLOAT'])) : '',
        '__file' => "{$trace['file']}:{$trace['line']}",
        '__sapi' => PHP_SAPI,
        '__data' => $data,
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK);

    $path = sprintf('%s/%s_%s.log',
        PATH_LOG, $type, date('ymd'));

    return file_put_contents($path, $log . PHP_EOL, FILE_APPEND);
}

/**
 * redis
 * @return \Predis\Client
 * @throws null
 */
function redis()
{
    static $client = null;

    if (!$client) {
        if (!class_exists('\Predis\Client')) {
            throw new AppException('composer require predis/predis');
        }

        $conf = config('redis');
        $client = new Predis\Client([
            'scheme' => $conf['scheme'],
            'host' => $conf['host'],
            'port' => $conf['port'],
        ], ['prefix' => $conf['prefix']]);
    }

    return $client;
}

/**
 * 消息队列
 * @return AppQueue
 * @throws null
 */
function queue()
{
    static $queue = null;

    if (!$queue) {
        if (!class_exists('\PhpAmqpLib\Connection\AMQPStreamConnection')) {
            throw new AppException('composer require php-amqplib/php-amqplib');
        }

        $queue = new AppQueue(config('rabbitmq'));
    }

    return $queue;
}

/**
 * aes
 * @return AppAes
 */
function aes()
{
    static $openssl = null;

    if (!$openssl) {
        $conf = config('app');
        $openssl = new AppAes($conf['secret_key']);
    }

    return $openssl;
}

/**
 * 网页后退
 */
function back()
{
    header('Location: ' . getenv('HTTP_REFERER'));
    exit;
}

/**
 * 网页跳转
 * @param string $url
 */
function redirect(string $url)
{
    header('Location: ' . $url);
    exit;
}

/**
 * 客户端IP
 * @return false|string
 */
function client_ip()
{
    $ip = '';
    if (isset($_SERVER['HTTP_CDN_SRC_IP'])) {
        $ip = $_SERVER['HTTP_CDN_SRC_IP'];
    } elseif (isset($_SERVER['HTTP_CLIENT_IP'])
        && preg_match('/^([0-9]{1,3}\.){3}[0-9]{1,3}$/', trim($_SERVER['HTTP_CLIENT_IP']))) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'])
        && preg_match_all('#\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}#s', trim($_SERVER['HTTP_X_FORWARDED_FOR']), $matches)) {
        foreach ($matches[0] AS $xip) {
            $xip = trim($xip);
            if (filter_var($xip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE)) {
                $ip = $xip;
                break;
            }
        }
    } elseif (isset($_SERVER['HTTP_X_REAL_IP'])) {
        $ip = $_SERVER['HTTP_X_REAL_IP'];
    } elseif (isset($_SERVER['REMOTE_ADDR'])) {
        $ip = $_SERVER['REMOTE_ADDR'];
    }

    return filter_var(trim($ip), FILTER_VALIDATE_IP);
}

/**
 * api 接口的 json 响应格式
 * @param array|Exception $data
 * @return string
 */
function json_response($data = [])
{
    if ($data instanceof Exception) {
        $response = [
            'state' => false,
            'code' => $data->getCode(),
            'message' => $data->getMessage(),
            'data' => new stdClass(),
        ];

        if ($data instanceof AppException) {
            $response['data'] = (object)$data->getData();
        }
    } else {
        $response = [
            'state' => true,
            'data' => (object)$data,
        ];
    }

    if (!headers_sent()) {
        header('Content-Type: application/json; Charset=UTF-8');
    }

    return json_encode($response);
}

/**
 * CSRF
 * @return AppCSRF
 */
function csrf()
{
    static $csrf = null;

    if (!$csrf) {
        $conf = config('app');
        $csrf = new AppCSRF([
            'secret_key' => $conf['secret_key'],
            'expire' => $conf['csrf_token_expire'],
        ]);
    }

    return $csrf;
}

/**
 * 闪存
 * @return AppFlash
 */
function flash()
{
    static $flash = null;

    if (!$flash) {
        $flash = new AppFlash();
    }

    return $flash;
}

/**
 * 登录信息
 * @return AppAuth
 */
function auth()
{
    static $auth = null;

    if (!$auth) {
        $auth = new AppAuth();
    }

    return $auth;
}

/**
 * 白名单
 * @return AppWhitelist
 */
function whitelist()
{
    static $whitelist = null;

    if (!$whitelist) {
        $whitelist = new AppWhitelist(config('whitelist'));
    }

    return $whitelist;
}

/**
 * 电子邮件
 * @return AppEmail
 * @throws null
 */
function email()
{
    static $email = null;

    if (!$email) {
        if (!class_exists('\Swift_SmtpTransport')) {
            throw new AppException('composer require swiftmailer/swiftmailer');
        }

        $email = new AppEmail(config('email'));
    }

    return $email;
}

/**
 * elasticsearch<br>
 * 文档 https://github.com/elastic/elasticsearch-php
 * @return \Elasticsearch\Client
 * @throws null
 */
function es()
{
    static $es = null;

    if (!$es) {
        if (!class_exists('\Elasticsearch\ClientBuilder')) {
            throw new AppException('composer require elasticsearch/elasticsearch');
        }

        $conf = config('elasticsearch');
        $hosts = $conf['hosts'];
        shuffle($hosts);

        $es = Elasticsearch\ClientBuilder::create()
            ->setHosts($hosts)
            ->build();
    }

    return $es;
}