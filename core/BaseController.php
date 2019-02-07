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

    /**
     * @inheritdoc
     * @return static
     */
    public static function instance()
    {
        return new static();
    }
}