<?php

namespace Core;

/**
 * 实例化类接口
 * @package Core
 */
interface InstanceInterface
{
    /**
     * 实例化自己
     * @return static
     */
    static function instance();
}