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
     * 解析 WHERE 语句
     * @param array $where
     * @return array
     */
    protected function parseWhere(array $where)
    {
        if (isset($where['0'])) { // 参数绑定方式
            $sqlWhere = $where[0];
            $binds = $where[1] ?? [];
        } else { // KV 的 AND 方式
            $placeholder = [];
            $binds = [];
            foreach ($where as $k => $v) {
                $placeholder[] = '`' . trim($k) . '`=?';
                $binds[] = $v;
            }

            $sqlWhere = implode(' AND ', $placeholder);
        }

        return [$sqlWhere, $binds];
    }

    /**
     * 解析表名
     * @param string $table
     * @return string
     */
    protected function parseTable(string $table)
    {
        $dbTable = explode('.', $table);
        foreach ($dbTable as &$v) {
            if (strpos($v, '`') === false) {
                $v = "`{$v}`";
            }
        }

        return implode('.', $dbTable);
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
    public function getOne(string $sql, array $binds = [])
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
    public function getAll(string $sql, array $binds = [])
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
     * @param array $data 要插入的数据 ['col0' => 1]
     * <p>value 使用原生 sql 时，应放在数组里 e.g. ['col0' => ['UNIX_TIMESTAMP()']]</p>
     * <p>支持单条 [...]; 或多条 [[...], [...]]</p>
     * @param string $table 表名
     * @param bool $ignore 是否使用 INSERT IGNORE 语法
     * @return int 返回最后插入的主键ID(批量插入时返回第1个ID), 失败时返回0
     */
    public function insert(array $data, string $table, $ignore = false)
    {
        // 单条插入时，一维转多维
        if (!isset($data[0])) {
            $data = [$data];
        }

        $columns = [];
        $values = [];
        $binds = [];
        foreach ($data as $num => $row) {
            $rowValues = [];

            foreach ($row as $k => $v) {
                if ($num == 0) {
                    $columns[] = '`' . trim($k) . '`';
                }

                if (is_array($v) && isset($v[0])) { // 原生不绑定
                    $rowValues[] = $v[0];
                } else {
                    $rowValues[] = '?';
                    $binds[] = $v;
                }
            }

            $values[] = '(' . implode(',', $rowValues) . ')';
        }

        $sqlColumn = implode(',', $columns);
        $sqlValues = implode(',', $values);
        $sqlIgnore = $ignore ? ' IGNORE' : '';
        $table = $this->parseTable($table);
        $sql = "INSERT{$sqlIgnore} INTO {$table}({$sqlColumn}) VALUES {$sqlValues}";

        $sql = $this->before($sql);
        $result = $this->query($sql, $binds);

        return $result;
    }

    /**
     * 更新记录
     * @param array $data 要更新的字段 ['col0' => 1]
     * <p>value 使用原生 sql 时，应放在数组里 e.g. ['col0' => ['col0+1']]</p>
     * @param array $where WHERE 条件
     * <p>KV: $where = ['col0' => 'foo']; 仅支持 AND 逻辑</p>
     * <p>参数绑定: $where = ['col0=?', ['foo']]; $where = ['col0=:c', ['c' => 'foo']]</p>
     * @param string $table 表名
     * @return int 被更新的行数，否则返回0(<b>注意：不要随便用来当判断条件</b>)
     */
    public function update(array $data, array $where, string $table)
    {
        if (empty($where)) {
            return 0;
        }

        $set = [];
        foreach ($data as $k => $v) {
            if (is_array($v) && isset($v[0])) { // 原生不加引号
                $v = $v[0];
            } else {
                $v = "'{$v}'";
            }

            $set[] = '`' . trim($k) . "`={$v}";
        }
        $sqlSet = implode(',', $set);

        list($sqlWhere, $binds) = $this->parseWhere($where);
        $table = $this->parseTable($table);
        $sql = "UPDATE {$table} SET {$sqlSet} WHERE {$sqlWhere}";

        $sql = $this->before($sql);
        $result = $this->query($sql, $binds);

        return $result;
    }

    /**
     * 删除记录
     * @param array $where WHERE 条件
     * <p>KV: $where = ['col0' => 'foo']; 仅支持 AND 逻辑</p>
     * <p>参数绑定: $where = ['col0=?', ['foo']]; $where = ['col0=:c', ['c' => 'foo']]</p>
     * @param string $table 表名
     * @return int 被删除的行数，否则返回0(<b>注意：不要随便用来当判断条件</b>)
     */
    public function delete(array $where, string $table)
    {
        if (empty($where)) {
            return 0;
        }

        list($sqlWhere, $binds) = $this->parseWhere($where);
        $table = $this->parseTable($table);
        $sql = "DELETE FROM {$table} WHERE {$sqlWhere}";

        $sql = $this->before($sql);
        $result = $this->query($sql, $binds);

        return $result;
    }

}
