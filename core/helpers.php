<?php

use Core\AppContainer;
use Core\AppException;
use Core\Auth;
use Core\Config;
use Core\CSRF;
use Core\Flash;
use Core\Request;
use Core\Translator;
use Core\Validator;
use duncan3dc\Laravel\BladeInstance;

/**
 * 取容器元素
 * @param string $name
 * @return mixed
 */
function app(string $name)
{
    return AppContainer::get($name);
}

/**
 * 设置容器里的元素
 * @param string $name
 * @param mixed $value 如果是回调函数，支持容器参数即 function (Container $pimple) {}
 */
function app_set(string $name, $value)
{
    $container = AppContainer::init();
    $container[$name] = $value;
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
 * 渲染视图模板
 * @param string $view 模板名
 * @param array $params 模板里的参数
 * @return string
 */
function view(string $view, array $params = [])
{
    /** @var BladeInstance $blade */
    $blade = app(BladeInstance::class);
    return $blade->render($view, $params);
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

    /** @var Auth $auth */
    $auth = app(Auth::class);
    $log = json_encode([
        '__time' => date('Y-m-d H:i:s'),
        '__index' => $index,
        '__requestid' => isset($_SERVER['REQUEST_TIME_FLOAT']) ? md5(strval($_SERVER['REQUEST_TIME_FLOAT'])) : '',
        '__file' => "{$trace['file']}:{$trace['line']}",
        '__sapi' => PHP_SAPI,
        '__uri' => $_SERVER['REQUEST_URI'] ?? '',
        '__agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
        '__userid' => $auth->getUserId(),
        '__data' => $data,
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK);

    $path = sprintf('%s/%s_%s.log',
        PATH_LOG, $type, date('ymd'));

    return file_put_contents($path, $log . PHP_EOL, FILE_APPEND);
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
    headers_sent() || header('Content-Type: application/json; Charset=UTF-8');

    $body = api_format($state, $data, $message, $code);
    $body['d'] = (object)$body['d'];

    return json_encode($body);
}

/**
 * 从 $_GET, $_POST 获取请求参数，支持payload
 * <p>
 * 简单用例：input('age') 即 $_POST['age'] <br>
 * 高级用例：input('post.age:i', 18, function ($val) { return $val+1; }) <br>
 * 即 $_POST['age']不存在时默认为18，最终返回 intval($_GET['age'])+1
 * @param string $field [(post|get|request).]<field_name>[.(i|b|a|f|d|s)]<br>
 * 参数池默认为 $_POST<br>
 * field_name 为字段名<br>
 * 类型强转：i=int, b=bool, a=array, f=float, d=double, s=string(默认)
 * @param mixed $default 默认值
 * @param callable $after 后置回调函数，其返回值将覆盖原字段值<br>
 * 回调函数格式为 function ($v, $k) {}<br>
 * </p>
 * @return mixed|Validator
 */
function input(string $field, $default = '', callable $after = null)
{
    /** @var Request $request */
    $request = app(Request::class);
    return $request->input($field, $default, $after);
}

/**
 * 读取所有请求参数，如果有验证则验证
 * @param bool $fetchNum 以非关联数组格式返回
 * @return array
 * @throws AppException
 */
function request(bool $fetchNum = false)
{
    /** @var Request $request */
    $request = app(Request::class);
    return $request->request($fetchNum);
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
