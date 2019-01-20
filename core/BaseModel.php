<?php


namespace Core;

/**
 * Class BaseModel
 * 模型基类
 * @package Core
 */
abstract class BaseModel implements InstanceInterface
{
    private function __construct()
    {
    }

    public static function instance()
    {
        return new static();
    }
}