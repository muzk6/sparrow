<?php


namespace Core;

/**
 * 模型基类
 * @package Core
 */
abstract class BaseModel implements InstanceInterface
{
    /**
     * @var string 表名
     */
    protected $table;

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