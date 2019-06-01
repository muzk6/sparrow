<?php

use Core\AppContainer;
use Core\AppException;
use Core\AppInput;

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
 * 支持依赖自动注入的函数调用
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
 * 配置文件
 * <p>优先从当前环境目录搜索配置文件</p>
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
 * 渲染视图模板
 * @param string $view 模板名
 * @param array $params 模板里的参数
 * @return string
 */
function view(string $view, array $params = [])
{
    return app(\duncan3dc\Laravel\BladeInstance::class)->render($view, $params);
}

/**
 * 文件日志
 * @param string $index 日志索引，用于正查和反查，建议传入 uniqid()
 * @param array|string $data 日志内容
 * @param string $type 日志类型，用于区分日志文件，不要带下划线前缀(用于区分框架日志)
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
        '__userid' => app(\Core\AppAuth::class)->getUserId(),
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
 * 获取、过滤、验证、类型强转 请求参数 $_GET,$_POST 支持payload
 * <p>
 * 简单用例：input('age') 取字段 age, 没指定 get,post，自动根据请求方法来决定使用 $_GET,$_POST <br>
 * 高级用例：input('get.age:i/年龄', 'number|gte:18', 18, function ($val) { return $val+1; }) <br>
 * 即 $_GET['age']不存在时默认为18，必须为数字且大于或等于18，验证通过后返回 intval($_GET['age'])+1
 * @param string $field get.field0:i/字段名0 即 intval($_GET['field0']) 标题为 字段名0
 * @param string|array|null $rules 验证规则，参考 \Core\AppInput::$errorMsg
 * @param mixed|null $default 默认值
 * @param callable|null $callback 自定义回调函数<br>
 * 回调函数格式为 function ($value, $title, $name) {}<br>
 * 有return: 以返回值为准 <br>
 * 无return: 字段值为用户输入值 <br>
 * 可抛出异常: AppException, Exception <br>
 * </p>
 * @return AppInput
 */
function input(string $field, $rules = null, $default = null, callable $callback = null)
{
    $input = AppInput::instance();
    return $input->input($field, $rules, $default, $callback);
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
    if (app(Redis::class)->lLen($key) < $limit) {
        $len = app(Redis::class)->lPush($key, $now);
    } else {
        $earliest = intval(app(Redis::class)->lIndex($key, -1));
        if ($now - $earliest < $ttl) {
            app(Redis::class)->expire($key, $ttl);
            panic('', [
                'reset' => $earliest + $ttl,
            ]);
        } else {
            app(Redis::class)->lTrim($key, 1, 0);
            $len = app(Redis::class)->lPush($key, $now);
        }
    }

    app(Redis::class)->expire($key, $ttl);
    return $limit - $len;
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
