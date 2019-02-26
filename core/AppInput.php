<?php

namespace Core;

use Exception;

/**
 * 请求的参数
 * @package Core
 */
class AppInput
{
    protected function single()
    {

    }

    protected function multi($all, callable $rParam)
    {
        $ret = [[], null];
        foreach ($all as $colName => $colVal) {
            $val = trim(strval($colVal));
            $ret[0][$colName] = $val;
            if (is_callable($rParam)) {
                try {
                    $callValue = $rParam($val, $colName);
                    is_null($callValue) || $ret[0][$colName] = $callValue;
                } catch (Exception $exception) {
                    $ret[1][$colName] = [
                        'code' => $exception->getCode(),
                        'message' => $exception->getMessage(),
                    ];

                    if ($exception instanceof AppException) {
                        $ret[1][$colName]['data'] = $exception->getData();
                    }
                }
            }
        }

        return $ret;
    }

    protected function pool($method)
    {
        switch ($method) {
            case 'get':
                $all = $_GET;
                break;
            case 'post':
                $all = $_POST;
                break;
            default:
                $all = array_merge($_GET, $_POST);
                break;
        }

        return $all;
    }

    protected function parse($key)
    {
        if (strpos($key, '.') !== false) {
            $keyDot = explode('.', $key);
            $all = $this->pool($keyDot[0]);
            $name = $keyDot[1];
        } else {
            $all = array_merge($_GET, $_POST);
            $name = $key;
        }

        return [$all, $name];
    }

    public function input($key, $rParam = '')
    {
        if (is_array($key)) {
            $all = [];

            if (isset($key[0])) {
                $all = $this->pool($key[0]);
            } else {
                $all = $this->pool('');
            }

            foreach ($key as $k => $v) {

            }

            $ret = $this->multi($all, $rParam);
        }

        // 所有
        if (empty($name)) {
            $ret = $this->multi($all, $rParam);
        } else { // 指定字段
            $val = isset($all[$name]) ? trim(strval($all[$name])) : null;
            $ret = [$val, null];
            if (is_callable($rParam)) {
                try {
                    $callValue = $rParam($val);
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
                $ret[0] = is_null($val) ? $rParam : $val;
            }
        }

        return $ret;

    }
}