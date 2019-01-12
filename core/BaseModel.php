<?php


namespace Core;

/**
 * Class BaseModel
 * 模型基类
 * @package Core
 */
class BaseModel implements InstanceInterface
{
    private function __construct()
    {
    }

    static function instance()
    {
        return new static();
    }
}