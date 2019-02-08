<?php


namespace Core;

/**
 * 模型基类
 * @package Core
 */
abstract class BaseModel implements InstanceInterface, ShardingInterface
{
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
        return new static();
    }

    /**
     * @inheritdoc
     * @param int|string $shardKey
     * @return $this|ShardingInterface
     */
    public function sharding($shardKey)
    {
        //todo 分区分表逻辑...

        if ($this->database) {
            $this->table = "{$this->database}.{$this->table}";
        }

        return $this;
    }

    /**
     * 数据库对象<br>
     * @return AppPDO|\PDO
     */
    public function db()
    {
        return db()->section($this->section)->table($this->table);
    }

}