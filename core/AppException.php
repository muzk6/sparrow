<?php

namespace Core;
use Exception;
use Throwable;

/**
 * Class AppException
 * 业务异常类
 */
class AppException extends Exception implements Throwable
{
    /**
     * @var array
     */
    protected $data;

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