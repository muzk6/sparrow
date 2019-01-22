<?php

use Core\AppException;
use Core\AppPDO;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
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
 * 消息队列发布
 * @param string $queue
 * @param array $data
 */
function queue_publish(string $queue, array $data)
{
    static $connection = null;
    static $channel = null;

    if (!$connection || !$channel) {
        $conf = config('rabbitmq');
        $connection = new AMQPStreamConnection($conf['host'], $conf['port'], $conf['user'], $conf['passwd']);

        $channel = $connection->channel();
        $channel->exchange_declare($conf['exchange_name'], $conf['exchange_type'], false, false, false);
        $channel->queue_declare($queue, false, true, false, false);
    }

    $msg = new AMQPMessage(
        json_encode($data),
        ['delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT]
    );

    $channel->basic_publish($msg, '', $queue);
}

/**
 * 消息队列消费
 * @param string $queue
 * @param callable $callback
 * @throws ErrorException
 */
function queue_consume(string $queue, callable $callback)
{
    if (PHP_SAPI != 'cli') {
        return;
    }

    $conf = config('rabbitmq');
    $connection = new AMQPStreamConnection($conf['host'], $conf['port'], $conf['user'], $conf['passwd']);

    $channel = $connection->channel();
    $channel->exchange_declare($conf['exchange_name'], $conf['exchange_type'], false, false, false);
    $channel->queue_declare($queue, false, true, false, false);
    $channel->basic_qos(null, 1, null);

    $channel->basic_consume($queue, '', false, false, false, false,
        function ($msg) use ($callback) {
            $callback(json_decode($msg->body, true));
            $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
        });

    static $fileStats = [];
    while (count($channel->callbacks)) {
        $includedFiles = get_included_files();
        foreach ($includedFiles as $includedFile) {
            clearstatcache(true, $includedFile);
            $mtime = filemtime($includedFile);
            $size = filesize($includedFile); // 同时比较文件大小，防止开发机与运行环境时间不一致

            // 记录、检查文件最后修改时间、大小，不同就直接结束进程(使用 supervisor 进行重启)
            if (!isset($fileStats[$includedFile])) {
                $fileStats[$includedFile] = ['mtime' => $mtime, 'size' => $size];
            } elseif ($fileStats[$includedFile]['mtime'] != $mtime
                || $fileStats[$includedFile]['size'] != $size) {
                $channel->close();
                $connection->close();
                exit;
            }
        }

        $channel->wait();
    }

    $channel->close();
    $connection->close();
}

/**
 * aes 对称加密
 * @param string $plainText 明文
 * @return array
 */
function aes_encrypt(string $plainText)
{
    $method = 'aes-256-cbc';
    $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($method), $isStrong);

    $key = md5(uniqid());
    $cipher = openssl_encrypt($plainText, $method, $key, 0, $iv);

    return [
        'cipher' => $cipher,
        'iv' => $iv,
        'key' => $key,
        'is_strong' => $isStrong,
    ];
}

/**
 * aes 对称解密
 * @param string $cipher base64格式的密文
 * @param string $iv 分组加密的初始向量
 * @param string $key 密钥
 * @return string 密文
 */
function aes_decrypt(string $cipher, $iv, $key)
{
    $method = 'aes-256-cbc';
    $plainText = openssl_decrypt($cipher, $method, $key, 0, $iv);

    return $plainText;
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
 * 返回csrf表单域
 * 会话初始化时才更新token
 * @return string
 */
function csrf_field()
{
    if (empty($_SESSION['csrf_token'])) {
        $token = sha1(uniqid(mt_rand(1000, 9999) . session_id()));
        $_SESSION['csrf_token'] = $token;
    } else {
        $token = $_SESSION['csrf_token'];
    }

    return '<input type="hidden" name="_token" value="' . $token . '">';
}

/**
 * csrf令牌校验
 * @return bool
 * @throws AppException
 */
function csrf_check()
{
    $token = $_POST['_token'] ?? '';
    if (empty($token)) {
        throw new AppException(10001002);
    }

    if (isset($_SESSION['csrf_token']) && $_SESSION['csrf_token'] == $token) {
        return true;
    }

    throw new AppException(10001002);
}

/**
 * 闪存设置
 * @param string $key
 * @param mixed $value
 * @return mixed
 */
function flash_set(string $key, $value)
{
    return $_SESSION[$key] = $value;
}

/**
 * 闪存是否存在
 * @param string $key
 * @return bool
 */
function flash_has(string $key)
{
    return isset($_SESSION[$key]);
}

/**
 * 闪存获取
 * @param string $key
 * @return null|mixed
 */
function flash_get(string $key)
{
    if (!flash_has($key)) {
        return null;
    }

    $value = $_SESSION[$key];
    unset($_SESSION[$key]);

    return $value;
}

/**
 * swift邮件实例
 * 简单文档 https://swiftmailer.symfony.com/docs/sending.html
 * 消息文档 https://swiftmailer.symfony.com/docs/messages.html
 * @return Swift_Mailer|null
 */
function swift_mailer()
{
    static $mailer = null;

    if (!$mailer) {
        $conf = config('mail');

        $transport = (new Swift_SmtpTransport($conf['host'], $conf['port'], $conf['encryption']))
            ->setUsername($conf['user'])
            ->setPassword($conf['passwd']);

        $mailer = new Swift_Mailer($transport);
    }

    return $mailer;
}

/**
 * swift邮件消息实例
 * @param string $subject 标题
 * @return Swift_Message
 */
function swift_message(string $subject)
{
    $conf = config('mail');
    $message = (new Swift_Message($subject))
        ->setFrom([$conf['user'] => $conf['name']]);

    return $message;
}