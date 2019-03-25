<?php

use Core\AppAuth;
use Core\AppCSRF;
use Core\AppEmail;
use Core\AppException;
use Core\AppFlash;
use Core\AppAes;
use Core\AppInput;
use Core\AppMessage;
use Core\AppPDO;
use Core\AppQueue;
use Core\AppWhitelist;
use Core\AppXdebug;

/**
 * 配置文件<br>
 * 优先从当前环境目录搜索配置文件
 * @param string $filename 无后缀的文件名
 * @return array|null 返回配置文件内容
 */
function config(string $filename)
{
    if (is_file($path = PATH_CONFIG_ENV . "/{$filename}.php")) {
        return include($path);
    } else if (is_file($path = PATH_CONFIG . "/{$filename}.php")) {
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
 * 实例化 App\Core\, Core\ 类
 * 优先实例化 App\Core\ 命名空间里的，不存在时才实例化 Core\ 里的
 * @param string $className 类名
 * @param mixed $args 构造函数的参数
 * @return stdClass|null
 */
function core(string $className, ...$args)
{
    $appCore = 'App\Core\\' . $className;
    if (class_exists($appCore)) {
        $core = $appCore;
    } else {
        $core = 'Core\\' . $className;
    }

    try {
        $reflector = new ReflectionClass($core);
        $instance = $reflector->newInstanceArgs($args);

        return $instance;
    } catch (ReflectionException $e) {
        return null;
    }
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
 * @param string $index 日志索引，用于正查和反查，建议传入 uniqid()
 * @param array|string $data 日志内容
 * @param string $type 日志类型，用于区分日志文件
 * @return false|int
 */
function logfile(string $index, $data, string $type = 'app')
{
    $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1)[0];
    $type = trim(str_replace('/', '', $type));

    $log = json_encode([
        '__time' => date('Y-m-d H:i:s'),
        '__index' => $index,
        '__requestid' => isset($_SERVER['REQUEST_TIME_FLOAT']) ? md5(strval($_SERVER['REQUEST_TIME_FLOAT'])) : '',
        '__file' => "{$trace['file']}:{$trace['line']}",
        '__sapi' => PHP_SAPI,
        '__uri' => $_SERVER['REQUEST_URI'] ?? '',
        '__agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
        '__data' => $data,
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK);

    $path = sprintf('%s/%s_%s.log',
        PATH_LOG, $type, date('ymd'));

    return file_put_contents($path, $log . PHP_EOL, FILE_APPEND);
}

/**
 * redis
 * @return \Redis
 * @throws null
 */
function redis()
{
    static $redis = null;

    if (!$redis) {
        if (!extension_loaded('redis')) {
            throw new AppException('pecl install redis');
        }

        $conf = config('redis');

        $redis = new Redis();
        $redis->pconnect($conf['host'], $conf['port']);
        $redis->setOption(Redis::OPT_PREFIX, $conf['prefix']);
    }

    return $redis;
}

/**
 * 消息队列
 * @return AppQueue
 * @throws null
 */
function queue()
{
    /** @var AppQueue $queue */
    static $queue = null;

    if (!$queue) {
        if (!class_exists('\PhpAmqpLib\Connection\AMQPStreamConnection')) {
            throw new AppException('composer require php-amqplib/php-amqplib');
        }

        $queue = core('AppQueue', config('rabbitmq'));
    }

    return $queue;
}

/**
 * aes
 * @return AppAes
 */
function aes()
{
    /* @var AppAes $openssl */
    static $openssl = null;

    if (!$openssl) {
        $conf = config('app');
        $openssl = core('AppAes', $conf['secret_key']);
    }

    return $openssl;
}

/**
 * 网页后退
 */
function back()
{
    header('Location: ' . getenv('HTTP_REFERER'));
}

/**
 * 网页跳转
 * @param string $url
 */
function redirect(string $url)
{
    header('Location: ' . $url);
}

/**
 * 客户端IP
 * @return false|string
 */
function ip()
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
 * 构造接口响应格式
 * @param array|stdClass|Exception|AppMessage $data
 * @return array
 */
function format2api($data)
{
    $response = [
        'state' => false,
        'code' => 0,
        'msg' => '',
        'data' => new stdClass(),
    ];

    if ($data instanceof Exception) {
        $response['code'] = intval($data->getCode());
        $response['msg'] = strval($data->getMessage());

        if ($data instanceof AppException) {
            $response['data'] = (object)$data->getData();
        }
    } else {
        $response['state'] = true;

        if ($data instanceof AppMessage) {
            $response['code'] = intval($data->getCode());
            $response['msg'] = strval($data->getMessage());
            $response['data'] = (object)$data->getData();
        } else {
            $response['data'] = is_array($data) ? (object)$data : $data;
        }
    }

    return $response;
}

/**
 * 获取、过滤、验证请求参数 $_GET,$_POST 支持payload<br>
 * list($data, $err) = input(...)<br>
 * 参数一 没指定 get,post 时，自动根据请求方法来决定使用 $_GET,$_POST
 * <p></p>
 *
 * <p>input('a', 10)<br>
 * -> eg. POST请求, !isset($_POST['a']) 时取默认值10</p>
 *
 * <p>input('get.a', function ($val) {return 'hello '.$val;})<br>
 * -> 'hello ' . $_GET['a']</p>
 *
 * <p>input('post.a', function ($val) {if (empty($val)) throw new AppException('...')})<br>
 * -> empty($_POST['a']) 时抛出异常</p>
 *
 * <p>input(), input(''), input('.')<br>
 * -> POST请求时，从 $_POST 里取全部；GET请求时，从 $_GET 里取全部</p>
 *
 * <p>input('post.')<br>
 * -> $_POST</p>
 *
 * <p>input(['get.a' => 10, 'post.b' => function ($val) {return 'hello '.$val;}, 'c'], function () {...})<br>
 * -> !isset($_GET['a']) 时取默认值10<br>
 * -> 'hello ' . $_POST['b']<br>
 * -> $_POST['c'], 参数c 没有定义默认值或回调，将会使用 input()参数二 来代替 </p>
 *
 * @param string|array $columns 单个或多个字段
 * @param mixed $defaultOrCallback 默认值或回调函数，优先级低于上面参数一的 array.value<br>
 * 回调函数格式为 function ($val, $name) {}<br>
 * 有return: 以返回值为准 <br>
 * 无return: 字段值为用户输入值 <br>
 * 抛出异常: AppException, Exception 将会被捕获到返回结果的数组[1]里，批量多字段的情况下如果[1]里都没有异常，则[1]的值就是 null<br>
 *
 * @return array [0 => [column => value], 1 => [column => error]]
 */
function input($columns = '', $defaultOrCallback = null)
{
    /** @var AppInput $input */
    static $input = null;

    if (!$input) {
        $input = new AppInput();
    }

    return $input->parse($columns, $defaultOrCallback);
}

/**
 * 频率限制
 * <p>ttl秒 内限制 limit次</p>
 * @param string $key 缓存key
 * @param int $limit 限制次数
 * @param int $ttl 指定秒数内
 * @return int 剩余次数
 * @throws AppException ['reset' => 重置时间]
 */
function throttle(string $key, int $limit, int $ttl)
{
    $now = time();
    if (redis()->lLen($key) < $limit) {
        $len = redis()->lPush($key, $now);
    } else {
        $earliest = redis()->lIndex($key, -1);
        if ($now - $earliest < $ttl) {
            redis()->expire($key, $ttl);
            panic('', [
                'reset' => $earliest + $ttl,
            ]);
        } else {
            redis()->lTrim($key, 1, 0);
            $len = redis()->lPush($key, $now);
        }
    }

    redis()->expire($key, $ttl);
    return $limit - $len;
}

/**
 * CSRF
 * @return AppCSRF
 */
function csrf()
{
    /** @var AppCSRF $csrf */
    static $csrf = null;

    if (!$csrf) {
        $conf = config('app');
        $csrf = core('AppCSRF', [
            'secret_key' => $conf['secret_key'],
            'expire' => $conf['csrf_token_expire'],
        ]);
    }

    return $csrf;
}

/**
 * 直接抛出业务异常对象
 * @param string|int|array $messageOrCode 错误码或错误消息<br>
 * 带有参数的状态码，使用 array: [10002001, 'name' => 'tom'] 或 [10002001, ['name' => 'tom']]
 * @param array $data 附加数组
 * @throws AppException
 */
function panic($messageOrCode = '', array $data = [])
{
    $exception = new AppException($messageOrCode);
    if ($data) {
        $exception->setData($data);
    }

    throw $exception;
}

/**
 * 成功消息结构
 * @param string|int|array $messageOrCode 状态码或文本消息<br>
 * 带有参数的状态码，使用 array: [10002001, 'name' => 'tom'] 或 [10002001, ['name' => 'tom']]
 * @param array $data 附带数组
 * @return AppMessage
 */
function message($messageOrCode = '', array $data = [])
{
    return new AppMessage($messageOrCode, $data);
}

/**
 * 闪存
 * @return AppFlash
 */
function flash()
{
    /** @var AppFlash $flash */
    static $flash = null;

    if (!$flash) {
        $flash = core('AppFlash');
    }

    return $flash;
}

/**
 * 用户登录信息
 * @return AppAuth
 */
function auth()
{
    /** @var AppAuth $auth */
    static $auth = null;

    if (!$auth) {
        $auth = core('AppAuth', 'AUTH:');
    }

    return $auth;
}

/**
 * 后台用户登录信息
 * @return AppAuth
 */
function admin()
{
    /** @var AppAuth $admin */
    static $admin = null;

    if (!$admin) {
        $admin = core('AppAuth', 'ADMIN:');
    }

    return $admin;
}

/**
 * 白名单
 * @return AppWhitelist
 */
function whitelist()
{
    /** @var AppWhitelist $whitelist */
    static $whitelist = null;

    if (!$whitelist) {
        $whitelist = core('AppWhitelist', config('whitelist'));
    }

    return $whitelist;
}

/**
 * Xdebug Trace
 * @return AppXdebug
 */
function xdebug()
{
    /** @var AppXdebug $xdebug */
    static $xdebug = null;

    if (!$xdebug) {
        $xdebug = core('AppXdebug');
    }

    return $xdebug;
}

/**
 * 电子邮件
 * @return AppEmail
 * @throws null
 */
function email()
{
    /** @var AppEmail $email */
    static $email = null;

    if (!$email) {
        if (!class_exists('\Swift_SmtpTransport')) {
            throw new AppException('composer require swiftmailer/swiftmailer');
        }

        $email = core('AppEmail', config('email'));
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
