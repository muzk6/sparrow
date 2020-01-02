<?php


namespace Core;


use PDO;

/**
 * 带固定连接资源的 PDO
 * @package Core
 */
class AppPDO
{
    /**
     * @var PDO 连接资源
     */
    protected $connection;

    /**
     * @var bool 日志开关
     */
    protected $openLog = false;

    public function __construct(PDO $connection, $openLog = false)
    {
        $this->connection = $connection;
        $this->openLog = $openLog;
    }

    /**
     * 获取连接资源 PDO
     * @return PDO
     */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * 前置勾子
     * @param string $sql
     * @return string
     */
    protected function before(string $sql)
    {
        $sql = trim($sql);
        if ($this->openLog) {
            logfile('sql', $sql, 'sql');
        }

        return $sql;
    }

    /**
     * 开启事务
     * @return bool
     */
    public function beginTransaction()
    {
        if (!$this->connection->inTransaction()) {
            return $this->connection->beginTransaction();
        }

        return false;
    }

    /**
     * 提交事务
     * @return bool
     */
    public function commit()
    {
        if ($this->connection->inTransaction()) {
            return $this->connection->commit();
        }

        return false;
    }

    /**
     * 回滚事务
     * @return bool
     */
    public function rollBack()
    {
        if ($this->connection->inTransaction()) {
            return $this->connection->rollBack();
        }

        return false;
    }

    /**
     * 查询一行记录
     * @param string $sql 原生 sql 语句
     * @param array $binds 防注入的参数绑定
     * @return array|false 无记录时返回 false
     */
    public function selectOne(string $sql, array $binds = [])
    {
        $sql = $this->before($sql);

        if (!preg_match('/limit\s+(?:\d+|\d+\,\d)\s*;?$/i', $sql)) {
            $sql .= ' LIMIT 1';
        }

        $statement = $this->connection->prepare($sql);
        $statement->execute($binds);

        return $statement->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * 查询多行记录
     * @param string $sql 原生 sql 语句
     * @param array $binds 防注入的参数绑定
     * @return array 无记录时返回空数组 []
     */
    public function selectAll(string $sql, array $binds = [])
    {
        $sql = $this->before($sql);

        $statement = $this->connection->prepare($sql);
        $statement->execute($binds);

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * 执行 insert, replace, update, delete 等增删改 sql 语句
     * @param string $sql 原生 sql 语句
     * @param array $binds 防注入的参数绑定
     * @return int 执行 insert, replace 时返回最后插入的主键ID, 失败时返回0
     * <br>其它语句返回受影响行数，否则返回0(<b>注意：不要随便用来当判断条件</b>)
     */
    public function query(string $sql, array $binds = [])
    {
        $sql = $this->before($sql);

        $statement = $this->connection->prepare($sql);
        $statement->execute($binds);

        if (preg_match('/^(insert|replace)\s/i', $sql)) {
            return intval($this->connection->lastInsertId());
        } else {
            return $statement->rowCount();
        }
    }

    /**
     * 插入记录
     * @param string $sql 原生 sql 语句
     * @param array $binds 防注入的参数绑定
     * @return int 返回最后插入的主键ID, 失败时返回0
     */
    public function insert(string $sql, array $binds = [])
    {
        $sql = $this->before($sql);

        $statement = $this->connection->prepare($sql);
        $statement->execute($binds);

        return intval($this->connection->lastInsertId());
    }

    /**
     * 更新记录
     * @param string $sql 原生 sql 语句
     * @param array $binds 防注入的参数绑定
     * @return int 被更新的行数，否则返回0(<b>注意：不要随便用来当判断条件</b>)
     */
    public function update(string $sql, array $binds = [])
    {
        $sql = $this->before($sql);

        $statement = $this->connection->prepare($sql);
        $statement->execute($binds);

        return $statement->rowCount();
    }

    /**
     * 删除记录
     * @param string $sql 原生 sql 语句
     * @param array $binds 防注入的参数绑定
     * @return int 被删除的行数，否则返回0(<b>注意：不要随便用来当判断条件</b>)
     */
    public function delete(string $sql, array $binds = [])
    {
        $sql = $this->before($sql);

        $statement = $this->connection->prepare($sql);
        $statement->execute($binds);

        return $statement->rowCount();
    }

}
