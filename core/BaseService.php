<?php

namespace Core;

/**
 * 服务基类
 * @package Core
 */
abstract class BaseService implements InstanceInterface
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