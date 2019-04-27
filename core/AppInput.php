<?php

namespace Core;

use Exception;

/**
 * 请求的参数
 * @package Core
 */
class AppInput
{
    private $error_msg = [
        'require' => ':attribute不能为空',
        'number' => ':attribute必须为数字',
        'array' => ':attribute必须为数组',
        'float' => ':attribute必须为浮点数',
        'boolean' => ':attribute必须为布尔值',
        'email' => ':attribute必须为正确的邮件地址',
        'url' => ':attribute必须为正确的url格式',
        'ip' => ':attribute必须为正确的ip地址',
        'timestamp' => ':attribute必须为正确的时间戳格式',
        'date' => ':attribute必须为正确的日期格式',
        'regex' => ':attribute格式不正确',
        'in' => ':attribute必须在:range内',
        'notIn' => ':attribute必须不在:range内',
        'between' => ':attribute必须在:1-:2范围内',
        'notBetween' => ':attribute必须不在:1-:2范围内',
        'max' => ':attribute最大值为:1',
        'min' => ':attribute最小值为:1',
        'length' => ':attribute长度必须为:1',
        'confirm' => ':attribute和:1不一致',
        'gt' => ':attribute必须大于:1',
        'lt' => ':attribute必须小于:1',
        'egt' => ':attribute必须大于等于:1',
        'elt' => ':attribute必须小于等于:1',
        'eq' => ':attribute必须等于:1',
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
    protected function key2name(string $key)
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
        if (strpos($name, ':') !== false) {
            $nameDot = explode(':', $name);
            $name = $nameDot[0];
            $type = $nameDot[1];
        }

        return [$bucket, $name, $type];
    }

    /**
     * 获取、过滤、验证、类型强转 请求参数 $_GET,$_POST 支持payload<br>
     * list($data, $err) = input(...)<br>
     * 参数一 没指定 get,post 时，自动根据请求方法来决定使用 $_GET,$_POST
     *
     * @see input()
     *
     * @param string|array $columns 单个或多个字段
     * @param mixed $defaultOrCallback 自定义回调函数，$columns 为 array 时无效<br>
     * 回调函数格式为 function ($val, $name) {}<br>
     * 有return: 以返回值为准 <br>
     * 无return: 字段值为用户输入值 <br>
     * 可抛出异常: AppException, Exception <br>
     *
     * @return array [0 => [column => value], 1 => [column => error]]
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
            list($bucket, $name, $type) = $this->key2name($rawItem['column']);
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

    public function parse2(string $field, $rules, $default = null, callable $callback = null)
    {
        list($bucket, $name, $type) = $this->key2name($field);

        $title = $name;
        $value = isset($bucket[$name]) ? trim(strval($bucket[$name])) : null; // 请求里没有指定参数时则为null
        $this->check($value, $rules, $title);

        $this->single($value, $name, $type, $default, $callback);
    }

    private function check($rules, $fieldValue, $fieldTitle)
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
            $ruleReverse = false;

            if ($rule[0] === '!') {
                $ruleReverse = true;
                $rule = ltrim($rule, '!');
            }

            if (strpos($rule, ':')) {
                $ruleSplit = explode(':', $rule);
                $ruleName = $ruleSplit[0];
                $ruleRange = $ruleSplit[1];

                $ruleValues = explode(',', $ruleSplit[1]);
                $ruleValue1 = $ruleValues[0] ?? '';
                $ruleValue2 = $ruleValues[1] ?? '';
            }

            if (!$this->checkResult($fieldValue, $ruleName, $ruleReverse, $ruleRange, $ruleValue1, $ruleValue2)) {
                $msg = $customMsg ? $customMsg : $this->getMessage($fieldTitle, $rule);
                return [false, $msg];
            }
        }

        return [true, null];
    }

    private function checkResult($fieldValue, string $ruleName, bool $ruleReverse, array $ruleRange, string $ruleValue1, string $ruleValue2)
    {
        $ret = false;
        switch ($ruleName) {
            case 'req':
            case 'require':
                $ret = !is_null($fieldValue);
                break;
            case 'num':
            case 'number':
                $ret = filter_var($fieldValue, FILTER_SANITIZE_NUMBER_INT);
                break;
            case 'arr':
            case 'array':
                $ret = is_array($fieldValue);
                break;
            case 'float':
                $ret = filter_var($fieldValue, FILTER_VALIDATE_FLOAT);
                break;
            case 'bool':
            case 'boolean':
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
            case 'between':
                $ret = ($fieldValue >= $ruleValue1) && ($fieldValue <= $ruleValue2);
                break;
            case 'max':
                $ret = $fieldValue <= $ruleValue1;
                break;
            case 'min':
                $ret = $fieldValue >= $ruleValue1;
                break;
            case 'len':
            case 'length':
                $length = is_array($fieldValue) ? count($fieldValue) : strlen($fieldValue);
                $ret = $length == $ruleValue1;
                break;
            case 'confirm'://todo 指定字段
                $ret = $fieldValue == $ruleValue1;
                break;
            case 'gt':
                $ret = $fieldValue > $ruleValue1;
                break;
            case 'lt':
                $ret = $fieldValue < $ruleValue1;
                break;
            case 'egt':
                $ret = $fieldValue >= $ruleValue1;
                break;
            case 'elt':
                $ret = $fieldValue <= $ruleValue1;
                break;
            case 'eq':
                $ret = $fieldValue == $ruleValue1;
                break;
            default:
                break;
        }

        return $ruleReverse ? !$ret : !!$ret;
    }

    private function getMessage(string $title, string $rule)
    {
        if (isset($this->error_msg[$error_key])) {
            return str_replace([':attribute', ':range', ':1', ':2'], [$title, $range, $value1, $value2], $this->error_msg[$error_key]);
        }
        return false;
    }

}
