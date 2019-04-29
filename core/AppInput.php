<?php

namespace Core;

use Exception;

/**
 * 请求的参数
 * @package Core
 */
class AppInput
{
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
     * 取出指定字段值
     * @param array &$refBucket $this->pool的参数集
     * @param string $name 字段名
     * @param string $type 需要强转为指定的类型
     * @param mixed|null $default
     * @param callable|null $callback
     * @return array [$val, $err]
     */
    protected function single($value, string $name, string $type, $default = null, callable $callback = null)
    {
        $ret = [$value, null];
        if (is_callable($callback)) {
            try {
                $callValue = $callback($value, $name);
                is_null($callValue) || $ret[0] = $callValue;
            } catch (Exception $exception) {
                $ret[1] = [
                    'code' => $exception->getCode(),
                    'msg' => $exception->getMessage(),
                ];

                if ($exception instanceof AppException) {
                    $ret[1]['data'] = $exception->getData();
                }
            }
        } else {
            $ret[0] = is_null($value) ? $default : $value;
        }

        switch ($type) {
            case 's':
                $ret[0] = strval($ret[0]);
                break;
            case 'i':
                $ret[0] = intval($ret[0]);
                break;
            case 'b':
                $ret[0] = boolval($ret[0]);
                break;
            case 'a':
                $ret[0] = (array)$ret[0];
                break;
            case 'f':
                $ret[0] = floatval($ret[0]);
                break;
        }

        return $ret;
    }

    /**
     * @var array|null
     */
    protected $payload = null;

    /**
     * 扁平处理
     * @param array $keys 索引键
     * @param array $groups 参数组
     * @return array
     */
    protected function flat(array $keys, array $groups)
    {
        $ret[0] = array_combine($keys, array_column($groups, 0));
        $ret[1] = array_combine($keys, array_column($groups, 1));
        array_filter($ret[1]) || $ret[1] = null;

        return $ret;
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
     * 解析键值，取对应请求类型的参数集、当前参数名、类型
     * @param string $key
     * @return array
     */
    protected function parse2(string $key)
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
     * 获取、过滤、验证、类型强转 请求参数 $_GET,$_POST 支持payload<br>
     * list($data, $err) = input(...)<br>
     * 参数一 没指定 get,post 时，自动根据请求方法来决定使用 $_GET,$_POST
     *
     * @param string|array $columns 单个或多个字段
     * @param mixed $defaultOrCallback 自定义回调函数，$columns 为 array 时无效<br>
     * 回调函数格式为 function ($val, $name) {}<br>
     * 有return: 以返回值为准 <br>
     * 无return: 字段值为用户输入值 <br>
     * 可抛出异常: AppException, Exception <br>
     *
     * @return array [0 => [column => value], 1 => [column => error]]
     * @see input()
     *
     */
    public function parse($columns = '', $defaultOrCallback = null)
    {
        $rawColumnWithDefCB = [];
        $isSingle = false;
        if (is_array($columns)) {
            foreach ($columns as $k => $v) {
                if (is_numeric($k)) { // eg. $columns=['col1','col2'] 即没有 value 也就是没有默认值回调
                    $column = $v;
                    $defCB = $defaultOrCallback;
                } else {
                    $column = $k;
                    $defCB = $v;
                }

                $rawColumnWithDefCB[] = [
                    'column' => $column,
                    'defCB' => $defCB
                ];
            }

        } else {
            foreach (explode(',', $columns) as $column) {
                $rawColumnWithDefCB[] = [
                    'column' => $column,
                    'defCB' => $defaultOrCallback
                ];
            }

            // 只有 input('col0') 才返回一维数组
            if (count($rawColumnWithDefCB) == 1) {
                $isSingle = true;
            }
        }

        $groups = [];
        $keys = [];
        foreach ($rawColumnWithDefCB as $rawItem) {
            list($bucket, $name, $type) = $this->parse2($rawItem['column']);
            if (empty($name)) { // 通配字段 eg. '', '.', 'get.' 'post.'
                foreach ($bucket as $k => $v) {
                    $groups[] = $this->single($bucket, $k, $type, $rawItem['defCB']);
                    $keys[] = $k;
                }
            } else { // 指定一个字段
                $groups[] = $this->single($bucket, $name, $type, $rawItem['defCB']);
                $keys[] = $name;
            }
        }

        $ret = (count($groups) == 1 && $isSingle) ? $groups[0] : $this->flat($keys, $groups);
        return $ret;
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


    public function input(string $field, $rules = null, $default = null, callable $callback = null)
    {
        $error = null;

        list($bucket, $fieldName, $fieldType, $fieldTitle) = $this->parse2($field);

        // 默认值
        $fieldValue = isset($bucket[$fieldName]) ? trim(strval($bucket[$fieldName])) : $default;

        // 表单验证
        if ($rules) {
            list($checkStatus, $msg) = $this->check($rules, $fieldValue, $fieldTitle);
            if (!$checkStatus) {
                $error = [
                    'code' => 0,
                    'msg' => $msg,
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

        return [$fieldValue, $error];
    }

    protected function check($rules, $fieldValue, $fieldTitle)
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
            if (!$this->checkResult($fieldValue, $ruleName, explode(',', $ruleRange), $ruleValue1, $ruleValue2)) {
                $msg = $customMsg ? $customMsg : $this->getMessage($fieldTitle, $ruleName, $ruleRange, $ruleValue1, $ruleValue2);
                return [false, $msg];
            }
        }

        return [true, null];
    }

    protected function checkResult($fieldValue, string $ruleName, array $ruleRange, string $ruleValue1, string $ruleValue2)
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
                list($bucket, $fieldName) = $this->parse2($ruleValue1);
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
                panic('校验规则不存在');
                break;
        }

        return !!$ret;
    }

    protected function getMessage(string $title, string $rule, string $range, string $value1, string $value2)
    {
        if (isset($this->errorMsg[$rule])) {
            if ($title) {
                $title = is_numeric($title) ? trans(intval($title)) : trim($title);
                $title = '"' . $title . '"';
            }

            return trans($this->errorMsg[$rule], ['name' => $title, 'range' => $range, '1' => $value1, '2' => $value2]);
        }
        return false;
    }

}
