<?php

namespace Core;

use PDO;

/**
 * 模型基类
 * @package Core
 */
abstract class BaseModel implements InstanceInterface
{
    /**
     * @var static 单例对象
     */
    protected static $instance = null;

    /**
     * @var string 分区名
     */
    protected $section = '';

    /**
     * @var string 数据库名
     */
    protected $database = '';

    /**
     * @var string 表名
     */
    protected $table = '';

    protected function __construct()
    {
    }

    /**
     * 分区分表逻辑<br>
     * 具体逻辑在子类中覆盖实现<br>
     * 修改 $this->section, $this->database, $this->table
     * @param int|string $index 分区分表索引值
     * @return static
     */
    protected function sharding($index)
    {
        return $this;
    }

    /**
     * 返回 database.table 格式的表名
     * @param int|string $index 分区分表索引值
     * @return string
     */
    public function getTable($index = '')
    {
        $index && $this->sharding($index);
        if ($this->database) {
            $table = "`{$this->database}`.`{$this->table}`";
        } else {
            $table = "`{$this->table}`";
        }

        return $table;
    }

    /**
     * @inheritdoc
     * @return static
     */
    public static function instance()
    {
        if (!static::$instance instanceof static) {
            static::$instance = new static();
        }

        return static::$instance;
    }

    /**
     * 模型数据库对象<br>
     * @return AppPDO|PDO
     */
    public static function db()
    {
        $instance = static::instance();
        return db()->section($instance->section)->table($instance->getTable());
    }

    /**
     * 模型分区分表数据库对象 ShardingDB
     * @param int|string $index 分区分表索引值
     * @return AppPDO|PDO
     */
    public static function sdb($index)
    {
        static::instance()->sharding($index);
        return static::db();
    }

}