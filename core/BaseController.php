<?php

namespace Core;

/**
 * Class BaseController
 * 控制器基类
 * @package Core
 */
abstract class BaseController implements InstanceInterface
{
    private function __construct()
    {
    }

    static function instance()
    {
        return new static();
    }
}