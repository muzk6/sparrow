<?php

namespace Core;

/**
 * 控制器基类
 * @package Core
 */
abstract class BaseController implements InstanceInterface
{
    protected function __construct()
    {
    }

    public static function instance()
    {
        return new static();
    }
}