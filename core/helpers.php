<?php

use Core\AppCSRF;
use Core\AppEmail;
use Core\AppException;
use Core\AppFlash;
use Core\AppOpenSSL;
use Core\AppPDO;
use Core\AppQueue;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use duncan3dc\Laravel\BladeInstance;

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
 * @return BladeInstance|null
 */
function view()
{
    static $blade = null;

    if (!$blade) {
        $blade = new BladeInstance(PATH_VIEW, PATH_DATA . '/views_cache');
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
 * 日志 monolog
 * @param string $name 日志器名称，也是日志文件名前缀
 * @return Logger
 * @throws Exception
 */
function monolog(string $name = 'app')
{
    static $logGroup = [];

    $log = &$logGroup[$name];
    if (!isset($log)) {
        $log = new Logger($name);

        $path = sprintf('%s/%s_%s_%s.log',
            PATH_LOG, PHP_SAPI, $name, date('Ym'));
        $log->pushHandler(new StreamHandler($path));
    }

    return $log;
}

/**
 * 缓存 redis
 * @return Predis\Client
 */
function redis()
{
    static $client = null;

    if (!$client) {
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
 */
function queue()
{
    static $queue = null;

    if (!$queue) {
        $queue = new AppQueue();
    }

    return $queue;
}

/**
 * openssl
 * @return AppOpenSSL
 */
function openssl()
{
    static $openssl = null;

    if (!$openssl) {
        $openssl = new AppOpenSSL();
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
 * api接口的json格式
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
        $csrf = new AppCSRF();
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
 * 电子邮件
 * @return AppEmail
 */
function email()
{
    static $email = null;

    if (!$email) {
        $email = new AppEmail(config('email'));
    }

    return $email;
}