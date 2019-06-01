<?php

namespace Core;

use Exception;
use Throwable;

/**
 * 业务异常类，支持 set/get 数组数据
 * @package Core
 */
final class AppException extends Exception implements Throwable
{
    /**
     * @var array
     */
    protected $data = [];

    /**
     * 设置附带抛出的数组
     * @param array $data
     * @return $this
     */
    public function setData(array $data)
    {
        $this->data = $data;
        return $this;
    }

    /**
     * 附带抛出的数组
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

}
