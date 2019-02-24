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
     * @param string|int $message
     * @param int $code
     * @param Throwable|null $previous
     */
    public function __construct($message = "", int $code = 0, Throwable $previous = null)
    {
        if (is_string($message)) {
            parent::__construct($message, $code, $previous);
        } else {
            parent::__construct(trans($message), $message, $previous);
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