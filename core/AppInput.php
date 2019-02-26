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
                    'message' => $exception->getMessage(),
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
                $bucket = $_POST;
                break;
            default:
                $bucket = array_merge($_GET, $_POST);
                break;
        }

        return $bucket;
    }

    /**
     * 获取并验证请求参数 list($data, $err) = input(...)<br>
     *
     * input('a', 10) 从 $_POST, $_GET 里取字段a, !isset(a) 时取默认值 10<br>
     * input('get.a', function ($val) {return 'hello ' . $val;}) 从 $_GET 里取字段a, 值带 hello 前缀<br>
     * input('post.a', function ($val, $name) {if (empty($val)) throw new AppException('...')})
     * 从 $_POST 里取字段a, empty 时抛出异常<br>
     * input() 从 $_POST, $_GET 里取所有字段<br>
     * input('post.') 从 $_POST 里取所有字段<br>
     * input(['get', 'a' => 10, 'b' => function () {...}]) 从 $_GET 里字段a, b <br>
     * input(['a' => 10, 'b' => function () {...}]) 从 $_POST, $_GET 里字段a, b <br>
     *
     * @param string|array|null $columns 单个或多个字段
     * @param mixed $defaultOrCallback 默认值或回调函数，$columns 为 array 时无效<br>
     * 回调函数格式为 function ($val, $name) {}<br>
     * 有return: 以返回值为准 <br>
     * 无return: 字段值为用户输入值 <br>
     * 可抛出异常: AppException, Exception <br>
     *
     * @return array [0 => [column => value], 1 => [column => error]]
     */
    public function parse($columns = null, $defaultOrCallback = null)
    {
        // 指定多个字段
        if (is_array($columns)) {
            if (isset($columns[0])) {
                $bucket = $this->pool($columns[0]);
                unset($columns[0]);
            } else {
                $bucket = $this->pool();
            }

            $groups = [];
            $keys = [];
            foreach ($columns as $k => $v) {
                $groups[] = $this->single($bucket, $k, $v);
                $keys[] = $k;
            }

            $ret = $this->flat($keys, $groups);
        } else {
            if (strpos($columns, '.') !== false) {
                $keyDot = explode('.', $columns);
                $bucket = $this->pool($keyDot[0]);
                $name = $keyDot[1];
            } else {
                $bucket = $this->pool();
                $name = $columns;
            }

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