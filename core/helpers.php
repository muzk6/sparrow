<?php

use Core\AppContainer;
use Core\AppCURL;
use Core\AppException;
use Core\Auth;
use Core\Blade;
use Core\Config;
use Core\CSRF;
use Core\Flash;
use Core\PDOEngine;
use Core\Queue;
use Core\Request;
use Core\Router;
use Core\Translator;
use Core\Validator;

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

        $actionParams = [];
        foreach ($ref->getParameters() as $param) {
            $actionParams[] = AppContainer::get($param->getClass()->getName());
        }

        return $ref->invokeArgs($actionParams);
    } catch (ReflectionException $e) {
        trigger_error($e->getMessage(), E_USER_ERROR);
    }
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
    if (is_array($keys)) {
        $ret = false;
        foreach ($keys as $k => $v) {
            $ret = app(Config::class)->set($k, $v);
        }

        return $ret;
    } else {
        return app(Config::class)->get($keys);
    }
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
 * 转换成当前语言的文本
 * @param int $code
 * @param array $params
 * @return string
 */
function trans(int $code, array $params = [])
{
    return app(Translator::class)->trans($code, $params);
}

/**
 * 文件日志
 * @param string $index 日志名(索引)
 * @param array|string $data 日志内容
 * @param string $filename 日志文件名前缀
 * @return int|null
 */
function logfile(string $index, $data, string $filename = 'app')
{
    if (defined('TEST_ENV')) {
        return null;
    }

    $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1)[0];
    $filename = trim(str_replace('/', '', $filename));

    $log = json_encode([
        'time' => date('Y-m-d H:i:s'),
        'index' => $index,
        'request_id' => isset($_SERVER['REQUEST_TIME_FLOAT']) ? md5(strval($_SERVER['REQUEST_TIME_FLOAT'])) : '',
        'file' => "{$trace['file']}:{$trace['line']}",
        'sapi' => PHP_SAPI,
        'hostname' => php_uname('n'),
        'url' => $_SERVER['REQUEST_URI'] ?? '',
        'method' => $_SERVER['REQUEST_METHOD'] ?? '',
        'ip' => app(Request::class)->getIp(),
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
        'user_id' => app(Auth::class)->getUserId(),
        'data' => $data,
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK);

    $path = sprintf('%s/%s_%s.log',
        PATH_LOG, $filename, date('Ym'));

    return file_put_contents($path, $log . PHP_EOL, FILE_APPEND);
}

/**
 * API 格式化
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
 * JSON 类型的 API 格式
 * @param int|bool|AppException|Exception $state 业务状态，异常对象时自动填充后面的参数
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
    if (empty($body['d'])) {
        $body['d'] = new stdClass();
    }

    return json_encode($body);
}

/**
 * 成功状态的 api_json()
 * @param string $message 消息体
 * @param int $code 消息码
 * @param array $data 对象体
 * @return string
 */
function api_success(string $message = '', int $code = 0, array $data = [])
{
    return api_json(true, $data, $message, $code);
}

/**
 * 失败状态的 api_json()
 * @param string $message 消息体
 * @param int $code 消息码
 * @param array $data 对象体
 * @return string
 */
function api_error(string $message = '', int $code = 0, array $data = [])
{
    return api_json(false, $data, $message, $code);
}

/**
 * PDOEngine 对象
 * @return PDOEngine
 */
function db()
{
    return app(PDOEngine::class);
}

/**
 * 定义模板变量
 * @param string $name
 * @param mixed $value
 * @return Blade
 */
function assign(string $name, $value)
{
    return app(Blade::class)->assign($name, $value);
}

/**
 * 渲染视图模板
 * @param string $view 模板名
 * @param array $params 模板里的参数
 * @return string
 */
function view(string $view, array $params = [])
{
    return app(Blade::class)->view($view, $params);
}

/**
 * 从 $_GET, $_POST 获取请求参数，支持 payload
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
 * @return mixed
 */
function input(string $field, $default = '', callable $after = null)
{
    return app(Request::class)->input($field, $default, $after);
}

/**
 * 从 $_GET, $_POST 获取请求参数，支持 payload
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
 * @return Validator
 */
function validate(string $field, $default = '', callable $after = null)
{
    return app(Request::class)->validate($field, $default, $after);
}

/**
 * 获取所有请求参数，如果有验证则验证
 * @param bool $inParallel false: 以串联短路方式验证；true: 以并联方式验证，即使前面的验证不通过，也会继续验证后面的字段
 * @return array
 * @throws AppException
 */
function request(bool $inParallel = false)
{
    return app(Request::class)->request($inParallel);
}

/**
 * 网页后退
 * <p>`back()` 网页跳转回上一步</p>
 */
function back()
{
    header('Location: ' . getenv('HTTP_REFERER'));
    exit;
}

/**
 * 网页跳转
 * <p>`redirect('/foo/bar')` 跳转到当前域名的`/foo/bar`地址去</p>
 * <p>`redirect('https://google.com')` 跳转到谷歌</p>
 * @param string $url
 */
function redirect(string $url)
{
    header('Location: ' . $url);
    exit;
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
            trigger_error("正确用法：url(['test', '/path/to'])", E_USER_ERROR);
        }

        list($alias, $path) = $path;
        $conf = config('domain');
        if (!isset($conf[$alias])) {
            trigger_error("domain.php 不存在配置项: {$alias}", E_USER_ERROR);
        }

        $host = $conf[$alias];
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
    return app(Request::class)->flash();
}

/**
 * 上次请求的字段值
 * @param string|null $name
 * @param string $default
 * @return mixed|null
 */
function old(string $name = null, string $default = '')
{
    return app(Request::class)->old($name, $default);
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
 * 闪存是否有值
 * @param string $key
 * @return bool true: 存在且为真
 */
function flash_has(string $key)
{
    return app(Flash::class)->has($key);
}

/**
 * 闪存是否存在
 * @param string $key
 * @return bool true: 存在，即使值为 null
 */
function flash_exists(string $key)
{
    return app(Flash::class)->exists($key);
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
 * 生成带有 token 的表单域 html 元素
 * @return string
 */
function csrf_field()
{
    return app(CSRF::class)->field();
}

/**
 * 获取 token
 * <p>会话初始化时才更新 token</p>
 * @return string
 */
function csrf_token()
{
    return app(CSRF::class)->token();
}

/**
 * 校验 token
 * @return true
 * @throws AppException
 */
function csrf_check()
{
    return app(CSRF::class)->check();
}

/**
 * 消息队列发布
 * @param string $queue 队列名称
 * @param array $data
 * @param string $exchangeName 交换器名称
 * @param string $exchangeType 交换器类型
 */
function queue_publish(string $queue, array $data, string $exchangeName = 'sparrow.direct', string $exchangeType = 'direct')
{
    app(Queue::class)->publish($queue, $data, $exchangeName, $exchangeType);
}

/**
 * 消息队列消费
 * @param string $queue 队列名称
 * @param callable $callback
 */
function queue_consume(string $queue, callable $callback)
{
    app(Queue::class)->consume($queue, $callback);
}

/**
 * 注册回调 GET 请求
 * @param string $url url全匹配
 * @param callable $action
 */
function route_get($url, callable $action)
{
    app(Router::class)->addRoute('GET', $url, $action);
}

/**
 * 注册回调 POST 请求
 * @param string $url url全匹配
 * @param callable $action
 */
function route_post($url, callable $action)
{
    app(Router::class)->addRoute('POST', $url, $action);
}

/**
 * 注册回调任何请求
 * @param string $url url全匹配
 * @param callable $action
 */
function route_any($url, callable $action)
{
    app(Router::class)->addRoute('ANY', $url, $action);
}

/**
 * 注册回调 GET 请求
 * @param string $url url正则匹配
 * @param callable $action
 */
function route_get_re($url, callable $action)
{
    app(Router::class)->addRoute('GET', $url, $action, ['url_type' => 'regexp']);
}

/**
 * 注册回调 POST 请求
 * @param string $url url正则匹配
 * @param callable $action
 */
function route_post_re($url, callable $action)
{
    app(Router::class)->addRoute('POST', $url, $action, ['url_type' => 'regexp']);
}

/**
 * 注册回调任何请求
 * @param string $url url正则匹配
 * @param callable $action
 */
function route_any_re($url, callable $action)
{
    app(Router::class)->addRoute('ANY', $url, $action, ['url_type' => 'regexp']);
}

/**
 * 注册路由中间件
 * @param callable $fn
 */
function route_middleware(callable $fn)
{
    app(Router::class)->addMiddleware($fn);
}

/**
 * 路由分组，隔离中间件
 * @param callable $fn
 */
function route_group(callable $fn)
{
    app(Router::class)->addGroup($fn);
}

/**
 * POST 请求
 * @param string|array $url
 * <p>string: 'http://sparrow.com/demo' 一般用于固定 url 的场景</p>
 * <p>array: ['rpc.sparrow', '/demo'] 即读取配置 domain.php 里的域名再拼接上 /demo 一般用于不同环境不同 url 的场景</p>
 * @param array $data POST 参数
 * @param array $headers 请求头
 * @param int $connectTimeout 请求超时(秒)
 * @return array|string|null
 */
function curl_post($url, array $data = [], array $headers = [], int $connectTimeout = 3)
{
    return app(AppCURL::class)->post($url, $data, $headers, $connectTimeout);
}

/**
 * GET 请求
 * @param string|array $url
 * <p>string: 'http://sparrow.com/demo' 一般用于固定 url 的场景</p>
 * <p>array: ['rpc.sparrow', '/demo'] 即读取配置 domain.php 里的域名再拼接上 /demo 一般用于不同环境不同 url 的场景</p>
 * @param array $data querystring 参数
 * @param array $headers 请求头
 * @param int $connectTimeout 请求超时(秒)
 * @return bool|string|null
 */
function curl_get($url, array $data = [], array $headers = [], int $connectTimeout = 3)
{
    return app(AppCURL::class)->get($url, $data, $headers, $connectTimeout);
}