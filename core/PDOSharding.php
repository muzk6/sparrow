<?php


namespace Core;

use PDO;

/**
 * 分片后连接资源的 PDO
 * @package Core
 */
class PDOSharding
{
    /**
     * @var string 数据库区域
     */
    public $section = '';

    /**
     * @var string `库名`.`表名`
     */
    public $table;

    /**
     * @var bool 日志开关
     */
    protected $openLog = false;

    /**
     * @param string $table 原始表名
     * @param string $index 分表依据
     * @param callable $shard 分表逻辑的回调函数
     * @param bool $openLog
     */
    public function __construct(string $table, string $index, callable $shard, $openLog = false)
    {
        $this->openLog = $openLog;

        $sharding = call_user_func_array($shard, [$table, $index]);
        $this->section = $sharding['section'] ?? '';

        if (!empty($sharding['dbname'])) {
            $this->table = "`{$sharding['dbname']}`.`{$sharding['table']}`";
        } else {
            $this->table = "`{$sharding['table']}`";
        }
    }

    /**
     * 获取连接资源 PDO
     * @param bool $useMaster
     * @return PDO
     */
    public function getConnection(bool $useMaster = false)
    {
        return app(PDOEngine::class)->getConnection($useMaster, $this->section);
    }

    /**
     * 开启事务，并返回连接资源
     * @return AppPDO|PDO
     */
    public function beginTransaction()
    {
        $connection = $this->getConnection(true);
        if (!$connection->inTransaction()) {
            $connection->beginTransaction();
        }

        return new AppPDO($connection, $this->openLog);
    }

    /**
     * 查询一行记录
     * @param string $sql 原生 sql 语句
     * @param array $binds 防注入的参数绑定
     * @param bool $useMaster 是否使用主库
     * @return array|false 无记录时返回 false
     */
    public function selectOne(string $sql, array $binds = [], bool $useMaster = false)
    {
        return (new AppPDO($this->getConnection($useMaster), $this->openLog))->selectOne($sql, $binds);
    }

    /**
     * 查询多行记录
     * @param string $sql 原生 sql 语句
     * @param array $binds 防注入的参数绑定
     * @param bool $useMaster 是否使用主库
     * @return array 无记录时返回空数组 []
     */
    public function selectAll(string $sql, array $binds = [], bool $useMaster = false)
    {
        return (new AppPDO($this->getConnection($useMaster), $this->openLog))->selectAll($sql, $binds);
    }

    /**
     * 插入记录
     * @param string $sql 原生 sql 语句
     * @param array $binds 防注入的参数绑定
     * @return int 返回最后插入的主键ID, 失败时返回0
     */
    public function insert(string $sql, array $binds = [])
    {
        return (new AppPDO($this->getConnection(true), $this->openLog))->insert($sql, $binds);
    }

    /**
     * 更新记录
     * @param string $sql 原生 sql 语句
     * @param array $binds 防注入的参数绑定
     * @return int 被更新的行数，否则返回0(<b>注意：不要随便用来当判断条件</b>)
     */
    public function update(string $sql, array $binds = [])
    {
        return (new AppPDO($this->getConnection(true), $this->openLog))->update($sql, $binds);
    }

    /**
     * 删除记录
     * @param string $sql 原生 sql 语句
     * @param array $binds 防注入的参数绑定
     * @return int 被删除的行数，否则返回0(<b>注意：不要随便用来当判断条件</b>)
     */
    public function delete(string $sql, array $binds = [])
    {
        return (new AppPDO($this->getConnection(true), $this->openLog))->delete($sql, $binds);
    }

}
