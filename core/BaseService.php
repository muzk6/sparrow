<?php

namespace Core;

/**
 * Class BaseService
 * 服务基类
 * @package Core
 */
abstract class BaseService implements InstanceInterface
{
    private function __construct()
    {
    }

    static function instance()
    {
        return new static();
    }
}