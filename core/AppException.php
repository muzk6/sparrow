<?php

namespace Core;

use Exception;
use Throwable;

/**
 * 业务异常类
 * @package Core
 */
final class AppException extends Exception implements Throwable
{
    /**
     * @var array
     */
    protected $data = [];

    /**
     * AppException constructor.
     * @param string|int $message 错误码或错误消息，错误码的情况下将忽略参数二
     * @param int $code 错误码
     * @param Throwable|null $previous 前一个异常对象
     */
    public function __construct($message = "", int $code = 0, Throwable $previous = null)
    {
        if (is_numeric($message)) {
            parent::__construct(trans($message), $message, $previous);
        } else {
            parent::__construct($message, $code, $previous);
        }
    }

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
