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
     * @var PDO
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

    public function __call($name, $arguments)
    {
        return call_user_func_array([$this->connection, $name], $arguments);
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
            logfile('AppDB', $sql, 'sql');
        }

        return $sql;
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
