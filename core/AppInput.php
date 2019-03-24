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
     * 取出指定字段值
     * @param array &$refBucket $this->pool的参数集
     * @param string $name 字段名
     * @param mixed $defaultOrCallback 默认值或回调函数
     * @return array
     */
    protected function single(array &$refBucket, string $name, $defaultOrCallback)
    {
        $val = isset($refBucket[$name]) ? trim(strval($refBucket[$name])) : null;
        $ret = [$val, null];
        if (is_callable($defaultOrCallback)) {
            try {
                $callValue = $defaultOrCallback($val, $name);
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
            $ret[0] = is_null($val) ? $defaultOrCallback : $val;
        }

        return $ret;
    }

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
                    $bucket = (array)json_decode(file_get_contents('php://input'), true);
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
     * 解析键值，取对应请求类型的参数集及当前参数名
     * @param string $key
     * @return array
     */
    protected function key2name(string $key)
    {
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

        return [$bucket, $name];
    }

    /**
     * 获取、过滤、验证请求参数 $_GET,$_POST 支持payload<br>
     * list($data, $err) = input(...)<br>
     * 参数一 没指定 get,post 时，自动根据请求方法来决定使用 $_GET,$_POST
     * @see input()
     *
     * @param string|array $columns 单个或多个字段
     * @param mixed $defaultOrCallback 默认值或回调函数，$columns 为 array 时无效<br>
     * 回调函数格式为 function ($val, $name) {}<br>
     * 有return: 以返回值为准 <br>
     * 无return: 字段值为用户输入值 <br>
     * 可抛出异常: AppException, Exception <br>
     *
     * @return array [0 => [column => value], 1 => [column => error]]
     */
    public function parse($columns = '', $defaultOrCallback = null)
    {
        // 指定多个字段
        if (is_array($columns)) {
            $groups = [];
            $keys = [];
            foreach ($columns as $k => $v) {
                if (is_numeric($k)) {
                    $column = $v;
                    $defCB = $defaultOrCallback;
                } else {
                    $column = $k;
                    $defCB = $v;
                }

                list($bucket, $name) = $this->key2name($column);
                $groups[] = $this->single($bucket, $name, $defCB);
                $keys[] = $name;
            }

            $ret = $this->flat($keys, $groups);
        } else {
            list($bucket, $name) = $this->key2name($columns);
            if (empty($name)) { // 所有字段
                $groups = [];
                $keys = [];
                foreach ($bucket as $k => $v) {
                    $groups[] = $this->single($bucket, $k, $defaultOrCallback);
                    $keys[] = $k;
                }

                $ret = $this->flat($keys, $groups);
            } else { // 指定一个字段
                $ret = $this->single($bucket, $name, $defaultOrCallback);
            }
        }

        return $ret;
    }
}
