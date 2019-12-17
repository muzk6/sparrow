<?php


namespace Core;


use Exception;

/**
 * HTTP请求体
 * @package Core
 */
class Request
{
    /**
     * @var null|array php://input
     */
    protected $payload = null;

    /**
     * @var array 验证模式的参数及验证对象集合
     */
    protected $validationSets = [];

    /**
     * 上次请求的参数
     * @var array
     */
    protected $oldRequest = [];

    /**
     * 客户端IP
     * @return string
     */
    public function getIp()
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

        return filter_var(trim($ip), FILTER_VALIDATE_IP) ?: '';
    }

    /**
     * 选择请求参数池 $_GET, $POST, $_REQUEST
     * @param null|string $method
     * @return array
     */
    protected function pool($method = null)
    {
        switch ($method) {
            case 'get':
                $bucket = &$_GET;
                break;
            case 'request':
                $bucket = &$_REQUEST;
                break;
            default:
                if (isset($_SERVER['HTTP_CONTENT_TYPE'])
                    && strpos(strtolower($_SERVER['HTTP_CONTENT_TYPE']), 'application/json') !== false) {
                    if (is_null($this->payload)) {
                        $this->payload = (array)json_decode(file_get_contents('php://input'), true);
                    }
                    $bucket = &$this->payload;
                } else {
                    $bucket = &$_POST;
                }
                break;
        }

        return $bucket;
    }

    /**
     * 解析键值
     * @param string $key field, post.field, field:i, post.field:i
     * @return array [参数集, 当前参数名, 类型]
     */
    protected function parse(string $key)
    {
        $key = trim($key);
        if (strpos($key, '.') !== false) {
            $keyDot = explode('.', $key);
            $bucket = $this->pool($keyDot[0]);
            $name = $keyDot[1];
        } else {
            $bucket = $this->pool();
            $name = $key;
        }

        $type = '';
        if (strpos($name, ':') !== false) {
            $nameDot = explode(':', $name);
            $name = $nameDot[0];
            $type = $nameDot[1];
        }

        return [$bucket, $name, $type];
    }

    /**
     * 类型转换
     * @param $value
     * @param string $type
     * @return array|bool|float|double|int|string
     */
    protected function convert($value, string $type)
    {
        switch ($type) {
            case 'i':
                $value = intval($value);
                break;
            case 'b':
                $value = boolval($value);
                break;
            case 'a':
                $value = (array)$value;
                break;
            case 'f':
                $value = floatval($value);
                break;
            case 'd':
                $value = doubleval($value);
                break;
            case 's':
            default:
                $value = strval($value);
                break;
        }

        return $value;
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
     * @return Validator
     */
    public function input(string $field, $default = '', callable $after = null)
    {
        list($bucket, $fieldName, $fieldType) = $this->parse($field);

        // 是否为空，取默认值
        if (isset($bucket[$fieldName]) && strlen(strval($bucket[$fieldName]))) {
            // 类型转换
            $fieldValue = $this->convert(trim(strval($bucket[$fieldName])), $fieldType);
        } else {
            $fieldValue = $default;
        }

        // 自定义后置回调
        if ($after) {
            $fieldValue = $after($fieldValue, $fieldName);
        }

        $validator = new Validator($fieldValue);
        $this->validationSets[$fieldName] = [
            'value' => $fieldValue,
            'validator' => $validator,
        ];

        return $validator;
    }

    /**
     * 读取所有请求参数，如果有验证则验证
     * @param bool $fetchNum 以非关联数组格式返回
     * @return array
     * @throws AppException
     */
    public function request(bool $fetchNum = false)
    {
        $data = [];
        $errors = [];
        foreach ($this->validationSets as $k => $v) {
            /** @var Validator $validator */
            $validator = $v['validator'];
            try {
                $validator->validate(true);
                if ($fetchNum) {
                    $data[] = $v['value'];
                } else {
                    $data[$k] = $v['value'];
                }
            } catch (Exception $exception) {
                $errors[$k] = $exception->getMessage();
            }
        }
        $this->validationSets = [];

        array_filter($errors) || $errors = null;
        if ($errors) {
            throw panic(10001000, $errors);
        }

        return $data;
    }

    /**
     * 把本次请求的参数缓存起来
     * @return bool
     */
    public function flash()
    {
        /** @var Flash $flash */
        $flash = app(Flash::class);
        return $flash->set('__oldRequest', array_merge($_GET, $_POST)) ? true : false;
    }

    /**
     * 上次请求的字段值
     * @param string|null $name
     * @param string $default
     * @return mixed|null
     */
    public function old(string $name = null, string $default = '')
    {
        if (!$this->oldRequest) {
            /** @var Flash $flash */
            $flash = app(Flash::class);
            $this->oldRequest = $flash->get('__oldRequest');
        }

        if ($name) {
            return $this->oldRequest[$name] ?? $default;
        } else {
            return $this->oldRequest;
        }
    }

}
