<?php


namespace Core;

/**
 * 模型基类
 * @package Core
 */
abstract class BaseModel implements InstanceInterface
{
    /**
     * @var static 单例对象
     */
    private static $instance = null;

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
     * @inheritdoc
     * @return static
     */
    public static function instance()
    {
        if (!self::$instance) {
            self::$instance = new static();
        }

        return self::$instance;
    }

    /**
     * 模型数据库对象<br>
     * @return AppPDO
     */
    public static function db()
    {
        $instance = self::instance();

        if ($instance->database) {
            $table = "{$instance->database}.{$instance->table}";
        } else {
            $table = $instance->table;
        }

        return db()->section($instance->section)->table($table);
    }

    /**
     * 模型分区分表数据库对象 ShardingDB
     * @param int|string $index
     * @return AppPDO
     */
    public static function sdb($index)
    {
        self::instance()->sharding($index);
        return self::db();
    }

}