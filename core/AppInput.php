<?php

namespace Core;

use Exception;

/**
 * 请求的参数
 * @package Core
 */
class AppInput
{
    /**
     * @var null|AppInput
     */
    protected static $instance = null;

    /**
     * php://input
     * @var null|array
     */
    protected $payload = null;

    /**
     * 所有 input() 的集合
     * @var array
     */
    protected $results = [];

    /**
     * 验证失败时的错误消息
     * @var array
     */
    protected $errorMsg = [
        'require' => 10001100,
        'number' => 10001101,
        'array' => 10001102,
        'float' => 10001103,
        'bool' => 10001104,
        'email' => 10001105,
        'url' => 10001106,
        'ip' => 10001107,
        'timestamp' => 10001108,
        'date' => 10001109,
        'regex' => 10001110,
        'in' => 10001111,
        'notIn' => 10001112,
        'between' => 10001113,
        'notBetween' => 10001114,
        'max' => 10001115,
        'min' => 10001116,
        'length' => 10001117,
        'confirm' => 10001118,
        'gt' => 10001119,
        'lt' => 10001120,
        'gte' => 10001121,
        'lte' => 10001122,
        'eq' => 10001123,
    ];

    /**
     * 单例对象
     * @return AppInput
     */
    public static function instance()
    {
        if (!self::$instance) {
            self::$instance = new static();
        }

        return self::$instance;
    }

    /**
     * 从 $_GET, $POST 里所有参数
     * @param null|string $method
     * @return array
     */
    protected function pool($method = null)
    {
        switch ($method) {
            case 'get':
                $bucket = $_GET;
                break;
            case 'post':
                if (isset($_SERVER['HTTP_CONTENT_TYPE'])
                    && strpos(strtolower($_SERVER['HTTP_CONTENT_TYPE']), 'application/json') !== false) {
                    if (is_null($this->payload)) {
                        $this->payload = (array)json_decode(file_get_contents('php://input'), true);
                    }
                    $bucket = $this->payload;
                } else {
                    $bucket = $_POST;
                }
                break;
            default:
                $bucket = $_REQUEST;
                break;
        }

        return $bucket;
    }

    /**
     * 解析键值，取对应请求类型的参数集、当前参数名、类型、参数标题
     * @param string $key
     * @return array [参数集, 当前参数名, 类型, 参数标题]
     */
    protected function parse(string $key)
    {
        $key = trim($key);
        if (strpos($key, '.') !== false) {
            $keyDot = explode('.', $key);
            $bucket = $this->pool($keyDot[0]);
            $name = $keyDot[1];
        } else {
            if (IS_GET) {
                $bucket = $this->pool('get');
            } elseif (IS_POST) {
                $bucket = $this->pool('post');
            } else {
                $bucket = $this->pool();
            }

            $name = $key;
        }

        $type = '';
        $title = '';
        if (strpos($name, ':') !== false) {
            $nameDot = explode(':', $name);
            $name = $nameDot[0];
            $type = $nameDot[1];

            if (strpos($type, '/') !== false) {
                $typeSlash = explode('/', $type);
                $type = $typeSlash[0];
                $title = $typeSlash[1];
            }
        } elseif (strpos($name, '/') !== false) {
            $nameSlash = explode('/', $name);
            $name = $nameSlash[0];
            $title = $nameSlash[1];
        }

        return [$bucket, $name, $type, $title];
    }

    /**
     * 类型转换
     * @param $value
     * @param string $type
     * @return array|bool|float|int|string
     */
    protected function convert($value, string $type)
    {
        switch ($type) {
            case 's':
                $value = strval($value);
                break;
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
        }

        return $value;
    }

    /**
     * 表单验证
     * @param string|array $rules
     * @param mixed $fieldValue
     * @param string $fieldTitle
     * @throws AppException
     */
    protected function validate($rules, $fieldValue, string $fieldTitle)
    {
        is_string($rules) && $rules = explode('|', $rules);
        foreach ($rules as $k => $v) {
            if (is_numeric($k)) { // 格式：['rule0', 'rule1']
                $rule = $v;
                $customMsg = null;
            } else { // 格式：['rule0' => 'message0', 'rule1' => 'message1']
                $rule = $k;
                $customMsg = $v;
            }

            $ruleValue1 = '';
            $ruleValue2 = '';
            $ruleRange = '';
            $ruleName = $rule;

            if (strpos($rule, ':')) {
                $ruleSplit = explode(':', $rule);
                $ruleName = $ruleSplit[0];
                $ruleRange = $ruleSplit[1];

                $ruleValues = explode(',', $ruleSplit[1]);
                $ruleValue1 = $ruleValues[0] ?? '';
                $ruleValue2 = $ruleValues[1] ?? '';
            }

            is_null($fieldValue) && $ruleName = 'require';
            if (!$this->check($fieldValue, $ruleName, explode(',', $ruleRange), $ruleValue1, $ruleValue2)) {
                if ($fieldTitle) {
                    $fieldTitle = is_numeric($fieldTitle) ? trans(intval($fieldTitle)) : trim($fieldTitle);
                    $fieldTitle = '"' . $fieldTitle . '"';
                }
                panic([$customMsg ?: $this->errorMsg[$ruleName], ['name' => $fieldTitle, 'range' => $ruleRange, '1' => $ruleValue1, '2' => $ruleValue2]]);
            }
        }
    }

    /**
     * 验证指定规则
     * @param mixed $fieldValue
     * @param string $ruleName
     * @param array $ruleRange
     * @param string $ruleValue1
     * @param string $ruleValue2
     * @return bool
     * @throws AppException
     */
    protected function check($fieldValue, string $ruleName, array $ruleRange, string $ruleValue1, string $ruleValue2)
    {
        $ret = false;
        switch ($ruleName) {
            case 'require':
                $ret = !is_null($fieldValue);
                break;
            case 'number':
                $ret = filter_var($fieldValue, FILTER_SANITIZE_NUMBER_INT);
                break;
            case 'array':
                $ret = is_array($fieldValue);
                break;
            case 'float':
                $ret = filter_var($fieldValue, FILTER_VALIDATE_FLOAT);
                break;
            case 'bool':
                $ret = filter_var($fieldValue, FILTER_VALIDATE_BOOLEAN);
                break;
            case 'email':
                $ret = filter_var($fieldValue, FILTER_VALIDATE_EMAIL);
                break;
            case 'url':
                $ret = filter_var($fieldValue, FILTER_SANITIZE_URL);
                break;
            case 'ip':
                $ret = filter_var($fieldValue, FILTER_VALIDATE_IP);
                break;
            case 'timestamp':
                $ret = strtotime(date('Y-m-d H:i:s', $fieldValue)) == $fieldValue;
                break;
            case 'date':
                $ret = strtotime($fieldValue) ? $fieldValue : false;
                break;
            case 'in':
                $ret = in_array($fieldValue, $ruleRange);
                break;
            case 'notIn':
                $ret = !in_array($fieldValue, $ruleRange);
                break;
            case 'between':
                $ret = ($fieldValue >= $ruleValue1) && ($fieldValue <= $ruleValue2);
                break;
            case 'notBetween':
                $ret = ($fieldValue >= $ruleValue1) && ($fieldValue <= $ruleValue2);
                break;
            case 'max':
                $ret = $fieldValue <= $ruleValue1;
                break;
            case 'min':
                $ret = $fieldValue >= $ruleValue1;
                break;
            case 'length':
                $length = is_array($fieldValue) ? count($fieldValue) : strlen($fieldValue);
                $ret = $length == $ruleValue1;
                break;
            case 'confirm':
                list($bucket, $fieldName) = $this->parse($ruleValue1);
                $ret = isset($bucket[$fieldName]) && $fieldValue == $bucket[$fieldName];
                break;
            case 'gt':
                $ret = $fieldValue > $ruleValue1;
                break;
            case 'lt':
                $ret = $fieldValue < $ruleValue1;
                break;
            case 'gte':
                $ret = $fieldValue >= $ruleValue1;
                break;
            case 'lte':
                $ret = $fieldValue <= $ruleValue1;
                break;
            case 'eq':
                $ret = $fieldValue == $ruleValue1;
                break;
            case 'regex':
                $ret = preg_match($ruleValue1, $fieldValue);
                break;
            default:
                panic('验证规则不存在');
                break;
        }

        return !!$ret;
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
     * @return mixed
     * @throws AppException
     */
    public function input(string $field, $rules = null, $default = null, callable $callback = null)
    {
        $error = null;

        list($bucket, $fieldName, $fieldType, $fieldTitle) = $this->parse($field);

        // 默认值
        $fieldValue = isset($bucket[$fieldName]) ? trim(strval($bucket[$fieldName])) : $default;

        // 表单验证
        if ($rules) {
            try {
                $this->validate($rules, $fieldValue, $fieldTitle);
            } catch (AppException $appException) {
                $error = [
                    'code' => $appException->getCode(),
                    'msg' => $appException->getMessage(),
                ];
            }
        }

        // 类型转换
        $fieldValue = $this->convert($fieldValue, $fieldType);

        // 自定义回调
        if ($callback) {
            try {
                $callValue = $callback($fieldValue, $fieldTitle, $fieldName);
                is_null($callValue) || $fieldValue = $callValue;
            } catch (Exception $exception) {
                $error = [
                    'code' => $exception->getCode(),
                    'msg' => $exception->getMessage(),
                ];

                if ($exception instanceof AppException) {
                    $error['data'] = $exception->getData();
                }
            }
        }

        $this->results[$fieldName] = [$fieldValue, $error];
        return $this;
    }

    /**
     * 返回所有请求字段的集合
     * <p>
     * list($req, $err) = collect();
     * </p>
     *
     * @return array <br>
     * 存在验证不通过的字段时：[['field0' => 'value0'], ['field0' => 'error message']] <br>
     * 所有验证通过且回调函数没异常时：[['field0' => 'value0'], null]
     */
    public function collect()
    {
        $results = $this->results;
        $this->results = [];

        $data = [];
        $error = [];
        foreach ($results as $fieldName => $result) {
            $data[$fieldName] = $result[0];
            $error[$fieldName] = $result[1];
        }
        array_filter($error) || $error = null;

        return [$data, $error];
    }

}
