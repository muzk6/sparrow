<?php

use Core\AppContainer;
use Core\AppException;
use Core\Auth;
use Core\Config;
use Core\CSRF;
use Core\DB;
use Core\Flash;
use Core\Request;
use Core\Translator;

/**
 * 获取/设置 容器元素
 * @param string $name
 * @param mixed $newValue null: 获取元素；其它值: 设置容器，如果是回调函数，支持容器参数即 function (Container $pimple) {}
 * @return mixed
 */
function app(string $name, $newValue = null)
{
    if (!is_null($newValue)) {
        $container = AppContainer::init();
        $container[$name] = $newValue;

        return $newValue;
    }

    return AppContainer::get($name);
}

/**
 * 支持自动依赖注入的函数调用
 * @param callable $fn
 * @return mixed
 */
function inject(callable $fn)
{
    try {
        $ref = new ReflectionFunction($fn);
    } catch (ReflectionException $e) {
        trigger_error($e->getMessage());
        return null;
    }

    $actionParams = [];
    foreach ($ref->getParameters() as $param) {
        $actionParams[] = AppContainer::get($param->getClass()->getName());
    }

    return $ref->invokeArgs($actionParams);
}

/**
 * 读取、设置 配置
 * <p>
 * 读取 config/dev/app.php 里的 lang 配置：config('app.lang')<br>
 * 设置：config(['app.lang' => 'en'])
 * </p>
 * @param string|array $keys string时读取，array时设置
 * @return bool|mixed
 */
function config($keys)
{
    /** @var Config $config */
    $config = app(Config::class);
    if (is_array($keys)) {
        $ret = false;
        foreach ($keys as $k => $v) {
            $ret = $config->set($k, $v);
        }

        return $ret;
    } else {
        return $config->get($keys);
    }
}

/**
 * 转换成当前语言的文本
 * @param int $code
 * @param array $params
 * @return string
 */
function trans(int $code, array $params = [])
{
    /** @var Translator $translator */
    $translator = app(Translator::class);
    return $translator->trans($code, $params);
}

/**
 * 文件日志
 * @param string $index 日志索引，用于正查和反查，建议传入 uniqid()
 * @param array|string $data 日志内容
 * @param string $type 日志类型，用于区分日志文件，不要带下划线前缀(用于区分框架日志)
 * @return int|null
 */
function logfile(string $index, $data, string $type = 'app')
{
    if (defined('TEST_ENV')) {
        return null;
    }

    $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1)[0];
    $type = trim(str_replace('/', '', $type));

    $log = json_encode([
        'time' => date('Y-m-d H:i:s'),
        'index' => $index,
        'request_id' => isset($_SERVER['REQUEST_TIME_FLOAT']) ? md5(strval($_SERVER['REQUEST_TIME_FLOAT'])) : '',
        'file' => "{$trace['file']}:{$trace['line']}",
        'sapi' => PHP_SAPI,
        'hostname' => php_uname('n'),
        'url' => $_SERVER['REQUEST_URI'] ?? '',
        'ip' => app(Request::class)->getIp(),
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
        'user_id' => app(Auth::class)->getUserId(),
        'data' => $data,
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK);

    $path = sprintf('%s/%s_%s.log',
        PATH_LOG, $type, date('ym'));

    return file_put_contents($path, $log . PHP_EOL, FILE_APPEND);
}

/**
 * API格式化
 * @param bool|AppException|Exception $state 业务状态，异常对象时自动填充后面的参数
 * @param array $data 对象体
 * @param string $message 消息体
 * @param int $code 消息码
 * @return array
 */
function api_format($state, array $data = [], string $message = '', int $code = 0)
{
    $body = [
        's' => false,
        'c' => $code,
        'm' => $message,
        'd' => $data,
    ];

    if ($state instanceof Exception) {
        $exception = $state;

        empty($code) && $body['c'] = $exception->getCode();
        empty($message) && $body['m'] = $exception->getMessage();

        if (empty($body['d']) && ($exception instanceof AppException)) {
            $body['d'] = $exception->getData();
        }
    } else {
        $body['s'] = boolval($state);
    }

    $body['s'] = boolval($body['s']);
    $body['c'] = intval($body['c']);
    $body['m'] = strval($body['m']);

    return $body;
}

/**
 * JSON类型的API格式
 * @param bool|AppException|Exception $state 业务状态，异常对象时自动填充后面的参数
 * @param array $data 对象体
 * @param string $message 消息体
 * @param int $code 消息码
 * @return string
 */
function api_json($state, array $data = [], string $message = '', int $code = 0)
{
    // 先刷出 buffer, 避免被后面的 header 影响
    if (ob_get_status()) {
        ob_flush();
        flush();
    }

    headers_sent() || header('Content-Type: application/json; Charset=UTF-8');

    $body = api_format($state, $data, $message, $code);
    $body['d'] = (object)$body['d'];

    return json_encode($body);
}

/**
 * 带协议和域名的完整URL
 * <p>
 * 当前域名URL：url('path/to')<br>
 * 其它域名URL：url(['test', '/path/to'])
 * </p>
 * @param string|array $path URL路径
 * @param array $params Query String
 * @param bool $secure 是否为安全协议
 * @return string
 */
function url($path, array $params = [], bool $secure = false)
{
    if (is_array($path)) {
        if (count($path) !== 2) {
            trigger_error("正确用法：url(['test', '/path/to'])");
            return '';
        }

        list($alias, $path) = $path;
        $host = config("domain.{$alias}");
    } else {
        $host = $_SERVER['HTTP_HOST'] ?? '';
    }

    if ($host) {
        $protocol = $secure ? 'https://' : 'http://';
        $host = $protocol . $host;
    }

    if ($params) {
        $path .= strpos($path, '?') !== false ? '&' : '?';
        $path .= http_build_query($params);
    }

    return $host . $path;
}

/**
 * 把本次请求的参数缓存起来
 * @return bool
 */
function request_flash()
{
    /** @var Request $request */
    $request = app(Request::class);
    return $request->flash();
}

/**
 * 上次请求的字段值
 * @param string|null $name
 * @param string $default
 * @return mixed|null
 */
function old(string $name = null, string $default = '')
{
    /** @var Request $request */
    $request = app(Request::class);
    return $request->old($name, $default);
}

/**
 * 闪存设置
 * @param string $key
 * @param mixed $value
 * @return mixed
 */
function flash_set(string $key, $value)
{
    return app(Flash::class)->set($key, $value);
}

/**
 * 闪存是否存在
 * @param string $key
 * @return bool
 */
function flash_has(string $key)
{
    return app(Flash::class)->has($key);
}

/**
 * 闪存获取并删除
 * @param string $key
 * @return null|mixed
 */
function flash_get(string $key)
{
    return app(Flash::class)->get($key);
}

/**
 * 闪存删除
 * @param string $key
 * @return true
 */
function flash_del(string $key)
{
    return app(Flash::class)->del($key);
}

/**
 * 抛出业务异常对象
 * @param string|int|array $messageOrCode 错误码或错误消息
 * <p>带有参数的错误码，使用 array: [10002001, 'name' => 'tom'] 或 [10002001, ['name' => 'tom']]</p>
 * @param array $data 附加数组
 * @throws AppException
 */
function panic($messageOrCode = '', array $data = [])
{
    if (is_array($messageOrCode)) {
        $code = $messageOrCode[0];
        $langParams = (isset($messageOrCode[1]) && is_array($messageOrCode[1]))
            ? $messageOrCode[1]
            : array_slice($messageOrCode, 1);
        $message = trans($code, $langParams);

        $exception = new AppException($message, $code);
    } elseif (is_int($messageOrCode)) {
        $code = $messageOrCode;
        $message = trans($code);
        $exception = new AppException($message, $code);
    } else {
        $message = $messageOrCode;
        $exception = new AppException($message);
    }

    $data && $exception->setData($data);
    throw $exception;
}

/**
 * 带有 token 的表单域 html 元素
 * @return string
 */
function csrf_field()
{
    /** @var CSRF $csrf */
    $csrf = app(CSRF::class);
    return $csrf->field();
}

/**
 * 令牌
 * <p>会话初始化时才更新 token</p>
 * @return string
 */
function csrf_token()
{
    /** @var CSRF $csrf */
    $csrf = app(CSRF::class);
    return $csrf->token();
}

/**
 * token 校验
 * @return true
 * @throws AppException
 */
function csrf_check()
{
    /** @var CSRF $csrf */
    $csrf = app(CSRF::class);
    return $csrf->check();
}

/**
 * DB 对象
 * @return DB
 */
function db()
{
    return app(DB::class);
}
