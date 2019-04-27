<?php


namespace Core;


/**
 *  用法
 *  use Validate\Validator;
 *
 *  $rules = [
 *       ['name|名字', 'require|email|in:7,8,9|max:10|min:6|between:6,8|length:2', '名字不能为空|名字必须必须为正确的邮件地址'],
 *       ['test|测试', 'require'],
 *   ];
 *  $data = ['name' => '8gAg:'];
 *  $validator = new AppValidator($rules);
 *  $validator->addRule(['name|名字', 'regex', '/^[0-8|a-z]+$/', '正则验证失败哦']);  //可以为二维数组，有|的正则验证只能通过addRule。
 *  $validator->validate($data);
 *  $validator->getAllErrors(); //获取所有验证错误 array
 *  $validator->getError();  //获取第一条验证错误 string
 *  Validator::in('7,8,9', 8);  //静态调用
 *  Validator::isEmail('375373223@qq.com');
 */
class AppValidator
{

    //错误信息
    private $error = [];

    //传入的验证规则
    private $validate = [];

    //需要验证的参数
    private $data = [];

    //添加的规则
    private $add_rules = [];

    //默认错误提示
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

    public function __construct($validate = null)
    {
        $this->validate = $validate;

    }

    /**
     * [validate 验证]
     * @param  [type] $data [需要验证的参数]
     * @return [type]       [boolean]
     */
    public function validate($data)
    {
        $this->data = $data;
        foreach ($this->validate as $key => $item) {
            $item_len = count($item);
            $name = $item[0];
            $rules = $item[1];

            $rules = explode('|', $rules);

            $message = $item_len > 2 ? explode('|', $item[2]) : null;

            $this->check($name, $rules, $message);
        }

        if (!empty($this->add_rules)) {
            $this->checkAddRules();
        }

        return empty($this->error) ? TRUE : FALSE;
    }

    /**
     * [check 单个字段验证]
     * @param  [type] $name    [description]
     * @param  [type] $rules   [description]
     * @param  [type] $message [description]
     * @return [type]          [null]
     */
    private function check($name, $rules, $message)
    {
        //$title = $name;
        $rule_name = $title = $name;
        if (strpos($name, '|')) {
            $rule_name = explode('|', $name)[0];
            $title = explode('|', $name)[1];
        }
        foreach ($rules as $i => $rule) {
            $validate_data = isset($this->data[$rule_name]) ? $this->data[$rule_name] : null;

            $result = $this->checkResult($rule, $validate_data);
            if (!$result) {
                $error_info = isset($message[$i]) ? $message[$i] : $this->getMessage($title, $rule);
                if ($error_info) {
                    array_push($this->error, $error_info);
                }
            }
        }
    }

    /**
     * [getMessage 获取验证失败的信息]
     * @param  [type] $name [字段名]
     * @param  [type] $rule [验证规则]
     * @return [type]       [string OR fail false]
     */
    private function getMessage($name, $rule)
    {
        $value1 = '';
        $value2 = '';
        $range = '';
        $error_key = $rule;
        if (strpos($rule, ':')) {
            $exp_arr = explode(':', $rule);
            $error_key = $exp_arr[0];
            $range = $exp_arr[1];
            $message_value = explode(',', $exp_arr[1]);
            $value1 = isset($message_value[0]) ? $message_value[0] : '';
            $value2 = isset($message_value[1]) ? $message_value[1] : '';
        }
        if (isset($this->error_msg[$error_key])) {
            return str_replace([':attribute', ':range', ':1', ':2'], [$name, $range, $value1, $value2], $this->error_msg[$error_key]);
        }
        return false;
    }

    /**
     * [checkResult 字段验证]
     * @param  [type] $rule          [验证规则]
     * @param  [type] $validate_data [需要验证的数据]
     * @return [type]                [boolean]
     */
    private function checkResult($rule, $validate_data)
    {
        switch ($rule) {
            case 'require':
                return $validate_data != '';
                break;
            case 'number':
                return filter_var($validate_data, FILTER_SANITIZE_NUMBER_INT);
                break;
            case 'array':
                return is_array($validate_data);
                break;
            case 'float':
                return filter_var($validate_data, FILTER_VALIDATE_FLOAT);
                break;
            case 'boolean':
                return filter_var($validate_data, FILTER_VALIDATE_BOOLEAN);
                break;
            case 'email':
                return filter_var($validate_data, FILTER_VALIDATE_EMAIL);
                break;
            case 'url':
                return filter_var($validate_data, FILTER_SANITIZE_URL);
            case 'ip':
                return filter_var($validate_data, FILTER_VALIDATE_IP);
                break;
            case 'timestamp':
                return strtotime(date('Y-m-d H:i:s', $validate_data)) == $validate_data;
                break;
            case 'date': //2017-11-17 12:12:12
                return strtotime($validate_data);
                break;
            default:
                if (strpos($rule, ':')) {
                    $rule_arr = explode(':', $rule);
                    $func_name = substr($rule, strpos($rule, ':') + 1);
                    return call_user_func_array([$this, $rule_arr[0]], [$func_name, $validate_data]);
                } else {
                    return call_user_func_array([$this, $rule], [$rule, $validate_data]);
                }
                break;
        }
    }

    /**
     * [regex 正则验证]
     * @param  [type] $rule [description]
     * @param  [type] $data [description]
     * @return [type]       [description]
     */
    public static function regex($rule, $data)
    {
        return filter_var($data, FILTER_VALIDATE_REGEXP, ["options" => ["regexp" => $rule]]);

    }

    /**
     * [addRule 添加规则格式 []]
     * @param [type] $rule [description]
     */
    public function addRule($rule)
    {
        if (is_array(current($rule))) {
            $this->add_rules = array_merge($this->add_rules, $rule);
        } else {
            array_push($this->add_rules, $rule);
        }
    }

    /**
     * [checkAddRules 添加新的规则的验证]
     * @return [type] [description]
     */
    public function checkAddRules()
    {
        foreach ($this->add_rules as $key => $item) {
            $name = $item[0];
            $message = isset($item[3]) ? $item[3] : null;
            $rule_name = $title = $name;
            if (strpos($name, '|')) {
                $rule_name = explode('|', $name)[0];
                $title = explode('|', $name)[1];
            }
            $validate_data = isset($this->data[$rule_name]) ? $this->data[$rule_name] : null;

            $result = $this->checkResult($item[1] . ':' . $item[2], $validate_data);
            if (!$result) {
                $error_info = isset($message) ? $message : $this->getMessage($title, $item[1]);
                if ($error_info) {
                    array_push($this->error, $error_info);
                }
            }
        }
    }

    /**
     * [in description]
     * @param  [type] $rule [验证规则]
     * @param  [type] $data [需要验证的数据]
     * @return [type]       [boolean]
     */
    public static function in($rule, $data)
    {
        if (!is_array($rule)) {
            $rule = explode(',', $rule);
        }
        return in_array($data, $rule);
    }

    /**
     * [in description]
     * @param  [type] $rule [验证规则]
     * @param  [type] $data [需要验证的数据]
     * @return [type]       [boolean]
     */
    public static function notIn($rule, $data)
    {
        return !self::in($data, $rule);
    }

    /**
     * [in description]
     * @param  [type] $rule [验证规则]
     * @param  [type] $data [需要验证的数据]
     * @return [type]       [boolean]
     */
    public static function between($rule, $data)
    {
        $rule = explode(',', $rule);
        return $data >= $rule[0] && $data <= $rule[1];
    }

    /**
     * [in description]
     * @param  [type] $rule [验证规则]
     * @param  [type] $data [需要验证的数据]
     * @return [type]       [boolean]
     */
    public static function notBetween($rule, $data)
    {
        return !$this->between($rule, $data);
    }

    /**
     * [in description]
     * @param  [type] $rule [验证规则]
     * @param  [type] $data [需要验证的数据]
     * @return [type]       [boolean]
     */
    public static function max($rule, $data)
    {
        return $data <= $rule;
    }

    /**
     * [in description]
     * @param  [type] $rule [验证规则]
     * @param  [type] $data [需要验证的数据]
     * @return [type]       [boolean]
     */
    public static function min($rule, $data)
    {
        return $data >= $rule;
    }

    /**
     * [in description]
     * @param  [type] $rule [验证规则]
     * @param  [type] $data [需要验证的数据]
     * @return [type]       [boolean]
     */
    public static function length($rule, $data)
    {
        $length = is_array($data) ? count($data) : strlen($data);
        return $length == $rule;
    }

    /**
     * [in description]
     * @param  [type] $rule [验证规则]
     * @param  [type] $data [需要验证的数据]
     * @return [type]       [boolean]
     */
    public static function confirm($rule, $data)
    {
        return isset($this->data[$rule]) && $data == $this->data[$rule];
    }

    public static function gt($rule, $data)
    {
        return $data > $rule;
    }

    public static function lt($rule, $data)
    {
        return $data < $rule;
    }

    public static function egt($rule, $data)
    {
        return $data >= $rule;
    }

    public static function elt($rule, $data)
    {
        return $data <= $rule;
    }

    public static function eq($rule, $data)
    {
        return $data == $rule;
    }

    /**
     * [in 获取验证失败的第一条信息]
     * @return [type]  [string]
     */
    public function getError()
    {

        return count($this->error) > 0 ? $this->error[0] : null;
    }

    /**
     * [getAllErrors 获取所有验证失败的信息]
     * @return [type] [array]
     */
    public function getAllErrors()
    {
        return $this->error;
    }

    /**
     * [__call 调用自定义函数或者]
     * @param  [type] $func [验证规则，函数名]
     * @param  [type] $data [验证数据]
     * @return [type]       [boollean]
     */
    function __call($func, $data)
    {
        $func_arr = get_defined_functions();
        if (in_array($func, $func_arr['user'])) {
            return call_user_func($func, $data);
        } else {
            array_push($this->error, '没有' . $func . '这个方法');
        }
    }

    /**
     * [__callStatic 静态方法调用自定义函数或者]
     * @param  [type] $func [验证规则，函数名]
     * @param  [type] $data [验证数据]
     * @return [type]       [boollean]
     */
    public static function __callStatic($func, $data)
    {
        if (substr($func, 0, 2) == 'is') {
            return call_user_func_array([new self, 'checkResult'], [strtolower(substr($func, 2)), $data[0]]);
        }
    }
}
