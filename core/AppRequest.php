<?php

namespace Core;

/**
 * 请求
 * @package Core
 */
class AppRequest
{
    /**
     * 强类型请求参数
     * @param string $method 参数存放的地方 get|post|request
     * @param string $type 参数类型 str|int|float|array
     * @param string $name 参数名字
     * @param mixed $default 默认值
     * @return array|float|int|string
     */
    protected function param(string $method, string $type, string $name, $default)
    {
        switch ($method) {
            case 'get':
                $raw = &$_GET[$name];
                break;
            case 'post':
                $raw = &$_POST[$name];
                break;
            case 'request':
                $raw = &$_REQUEST[$name];
                break;
            default:
                return $default;
        }

        $data = $default;
        if (isset($raw)) {
            switch ($type) {
                case 'str':
                    $data = trim(strval($raw));
                    break;
                case 'int':
                    $data = intval($raw);
                    break;
                case 'float':
                    $data = floatval($raw);
                    break;
                case 'array':
                    $data = (array)($raw);
                    break;
            }
        }

        return $data;
    }

    /**
     * 强类型 $_GET[$name]
     * @param string $name 参数名
     * @param string $default 默认值
     * @return string
     */
    public function getStr(string $name, $default = '')
    {
        return $this->param('get', 'str', $name, $default);
    }

    /**
     * 强类型 $_POST[$name]
     * @param string $name 参数名
     * @param string $default 默认值
     * @return string
     */
    public function postStr(string $name, $default = '')
    {
        return $this->param('post', 'str', $name, $default);
    }

    /**
     * 强类型 $_REQUEST[$name]
     * @param string $name 参数名
     * @param string $default 默认值
     * @return string
     */
    public function requestStr(string $name, $default = '')
    {
        return $this->param('request', 'str', $name, $default);
    }

    /**
     * 强类型 $_GET[$name]
     * @param string $name 参数名
     * @param int $default 默认值
     * @return int
     */
    public function getInt(string $name, $default = 0)
    {
        return $this->param('get', 'int', $name, $default);
    }

    /**
     * 强类型 $_POST[$name]
     * @param string $name 参数名
     * @param int $default 默认值
     * @return int
     */
    public function postInt(string $name, $default = 0)
    {
        return $this->param('post', 'int', $name, $default);
    }

    /**
     * 强类型 $_REQUEST[$name]
     * @param string $name 参数名
     * @param int $default 默认值
     * @return int
     */
    public function requestInt(string $name, $default = 0)
    {
        return $this->param('request', 'int', $name, $default);
    }

    /**
     * 强类型 $_GET[$name]
     * @param string $name 参数名
     * @param float $default 默认值
     * @return float
     */
    public function getFloat(string $name, $default = 0.0)
    {
        return $this->param('get', 'float', $name, $default);
    }

    /**
     * 强类型 $_POST[$name]
     * @param string $name 参数名
     * @param float $default 默认值
     * @return float
     */
    public function postFloat(string $name, $default = 0.0)
    {
        return $this->param('post', 'float', $name, $default);
    }

    /**
     * 强类型 $_REQUEST[$name]
     * @param string $name 参数名
     * @param float $default 默认值
     * @return float
     */
    public function requestFloat(string $name, $default = 0.0)
    {
        return $this->param('request', 'float', $name, $default);
    }

    /**
     * 强类型 $_GET[$name]
     * @param string $name 参数名
     * @param array $default 默认值
     * @return array
     */
    public function getArray(string $name, $default = [])
    {
        return $this->param('get', 'array', $name, $default);
    }

    /**
     * 强类型 $_POST[$name]
     * @param string $name 参数名
     * @param array $default 默认值
     * @return array
     */
    public function postArray(string $name, $default = [])
    {
        return $this->param('post', 'array', $name, $default);
    }

    /**
     * 强类型 $_REQUEST[$name]
     * @param string $name 参数名
     * @param array $default 默认值
     * @return array
     */
    public function requestArray(string $name, $default = [])
    {
        return $this->param('request', 'array', $name, $default);
    }

    /**
     * 是否为 POST 请求
     * @return bool
     */
    public function isPost()
    {
        return isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] == 'POST' : false;
    }

    /**
     * 是否为 GET 请求
     * @return bool
     */
    public function isGet()
    {
        return isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] == 'GET' : false;
    }

}