<?php


namespace Core;

/**
 * 模型基类
 * @package Core
 */
abstract class BaseModel implements InstanceInterface
{
    protected function __construct()
    {
    }

    public static function instance()
    {
        return new static();
    }
}