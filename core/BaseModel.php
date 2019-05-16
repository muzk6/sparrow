<?php

namespace Core;

use PDO;

/**
 * 模型基类
 * @package Core
 */
abstract class BaseModel
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
    public function getTable($index = '')
    {
        $index && $this->sharding($index);

        if ($this->database) {
            $table = $this->quote($this->database) . '.' . $this->quote($this->table);
        } else {
            $table = $this->quote($this->table);
        }

        return $table;
    }

    /**
     * 模型数据库对象<br>
     * @return AppPDO|PDO
     */
    public function db()
    {
        return db()->section($this->section)->table($this->getTable());
    }

    /**
     * 模型分区分表数据库对象 ShardingDB
     * @param int|string $index 分区分表索引值
     * @return AppPDO|PDO
     */
    public function sdb($index)
    {
        $this->sharding($index);
        return $this->db();
    }

}
