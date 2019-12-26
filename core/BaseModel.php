<?php

namespace Core;

/**
 * 模型基类
 * @package Core
 */
abstract class BaseModel
{
    /**
     * @var string 连接资源的分区名
     */
    public const SECTION = 'default';

    /**
     * @var string 数据库名
     */
    public const DB_NAME = '';

    /**
     * @var string 表名
     */
    public const TABLE_NAME = '';

    /**
     * 分区分库分表逻辑，子类覆盖时必须按此结构返回
     * <p>具体逻辑在子类中覆盖实现</p>
     * <p>修改 $this->section, $this->dbName, $this->tableName</p>
     * @param int|string $index 分区分表索引值
     * @return array
     */
    public function sharding($index)
    {
        return [
            'section' => static::SECTION,
            'db_name' => static::DB_NAME,
            'table_name' => static::TABLE_NAME,
        ];
    }

}
