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
     * 反引号修饰处理
     * @param string $name
     * @return string
     */
    protected function quote(string $name)
    {
        $name = trim($name);
        if (strpos($name, '`') === false) {
            $name = "`{$name}`";
        }

        return $name;
    }

    /**
     * 返回带反引号的表名(支持指定数据库)<br>
     * <p>table -> `table`</p>
     * <p>database.table -> `database`.`table`</p>
     * @param int|string $index 分区分表索引值
     * @return string
     */
    public static function table($index = '')
    {
        $instance = static::instance();
        $index && $instance->sharding($index);

        if ($instance->database) {
            $table = $instance->quote($instance->database) . '.' . $instance->quote($instance->table);
        } else {
            $table = $instance->quote($instance->table);
        }

        return $table;
    }

    /**
     * 模型数据库对象<br>
     * @return AppPDO|PDO
     */
    public static function db()
    {
        $instance = static::instance();
        return db()->section($instance->section)->table(static::table());
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