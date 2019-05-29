<?php

namespace Core;

/**
 * 模型基类
 * @package Core
 */
abstract class BaseModel extends AppPDO
{
    /**
     * @var string 连接资源的分区名
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
     * 分区分表逻辑
     * <p>具体逻辑在子类中覆盖实现</p>
     * <p>修改 $this->section, $this->database, $this->table</p>
     * @param int|string $index 分区分表索引值
     * @return static|PDO
     */
    public function sharding($index)
    {
        return $this;
    }

}
