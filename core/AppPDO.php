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
    protected $openLog;

    /**
     * @var string 表名
     */
    protected $table;

    public function __construct(PDO $connection, $openLog = false, string $table = '')
    {
        $this->connection = $connection;
        $this->openLog = $openLog;
        $this->table = $table;
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
     * 解析 SQL 语句
     * @param string $sql
     * @return string
     */
    protected function parseSql(string $sql)
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
        // 把一维 参数绑定 ['col0=?', 1]; ['col0=?', [1]] 转为二维
        if (isset($where[0]) && is_string($where[0])) {
            $where = [$where];
        }

        $placeholder = [];
        $binds = [];
        foreach ($where as $k => $v) {
            if (is_array($v)) { // 二维参数绑定
                if (isset($v[0])) { // 占位符
                    $placeholder[] = trim($v[0]);
                }

                if (isset($v[1])) { // 参数绑定
                    if (is_array($v[1])) { // ['between', [1, 10]]
                        $binds = array_merge($binds, $v[1]);
                    } else { // ['between', 1, 10]
                        $binds = array_merge($binds, array_slice($v, 1));
                    }
                }
            } else { // 普通 AND KV
                $placeholder[] = 'AND `' . trim($k) . '`=?';
                $binds[] = $v;
            }
        }

        $placeholder = implode(' ', $placeholder);
        $placeholder = preg_replace('/^and\s/i', '', trim($placeholder));
        if (empty($placeholder)) {
            trigger_error('WHERE 条件不能为空');
        }

        return [$placeholder, $binds];
    }

    /**
     * 解析表名
     * @param string $table
     * @return string
     */
    protected function parseTable(string $table)
    {
        $dbTable = explode('.', $table ?: $this->table);
        foreach ($dbTable as &$v) {
            if (strpos($v, '`') === false) {
                $v = "`{$v}`";
            }
        }

        return implode('.', $dbTable);
    }

    /**
     * 解析 ORDER BY 语句
     * @param string $orderBy
     * @return string
     */
    protected function parseOrderBy(string $orderBy)
    {
        return $orderBy ? "ORDER BY {$orderBy}" : '';
    }

    /**
     * 解析 LIMIT 语句
     * @param array $limit
     * @return string
     */
    protected function parseLimit(array $limit = [])
    {
        return $limit ? 'LIMIT ' . implode(',', $limit) : '';
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
        $sql = $this->parseSql($sql);

        try {
            if (!preg_match('/limit\s+(?:\d+|\d+\,\d)\s*;?$/i', $sql)) {
                $sql .= ' LIMIT 1';
            }

            $statement = $this->connection->prepare($sql);
            $statement->execute($binds);

            return $statement->fetch(PDO::FETCH_ASSOC);
        } catch (\PDOException $PDOException) {
            trigger_error($sql . '; ' . $PDOException->getMessage(), E_USER_ERROR);
        }
    }

    /**
     * 查询多行记录
     * @param string $sql 原生 sql 语句
     * @param array $binds 防注入的参数绑定
     * @return array 无记录时返回空数组 []
     */
    public function getAll(string $sql, array $binds = [])
    {
        $sql = $this->parseSql($sql);

        try {
            $statement = $this->connection->prepare($sql);
            $statement->execute($binds);

            return $statement->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $PDOException) {
            trigger_error($sql . '; ' . $PDOException->getMessage(), E_USER_ERROR);
        }
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
        $sql = $this->parseSql($sql);

        try {
            $statement = $this->connection->prepare($sql);
            $statement->execute($binds);

            if (preg_match('/^(insert|replace)\s/i', $sql)) {
                return intval($this->connection->lastInsertId());
            } else {
                return $statement->rowCount();
            }
        } catch (\PDOException $PDOException) {
            trigger_error($sql . '; ' . $PDOException->getMessage(), E_USER_ERROR);
        }
    }

    /**
     * 查询一行记录
     * @param string $columns 查询字段
     * @param array $where WHERE 条件
     * <p>KV: $where = ['col0' => 'foo']; 仅支持 AND 逻辑</p>
     * <p>参数绑定: $where = ['col0=?', ['foo']]; $where = ['col0=:c', ['c' => 'foo']]</p>
     * @param string $table 表名，分表情况下允许为空(自动切换为分表 $table)
     * @param string $orderBy ORDER BY 语法 e.g. 'id DESC'
     * @return array|false 无记录时返回 false
     */
    public function selectOne(string $columns, array $where, string $table = '', string $orderBy = '')
    {
        list($placeholder, $binds) = $this->parseWhere($where);
        if (empty($placeholder)) {
            return false;
        }

        $table = $this->parseTable($table);
        $orderBy = $this->parseOrderBy($orderBy);
        $sql = "SELECT {$columns} FROM {$table} WHERE {$placeholder} {$orderBy}";
        $result = $this->getOne($sql, $binds);

        return $result;
    }

    /**
     * 查询多行记录
     * @param string $columns 查询字段
     * @param array $where WHERE 条件
     * <p>KV: $where = ['col0' => 'foo']; 仅支持 AND 逻辑</p>
     * <p>参数绑定: $where = ['col0=?', ['foo']]; $where = ['col0=:c', ['c' => 'foo']]</p>
     * @param string $table 表名，分表情况下允许为空(自动切换为分表 $table)
     * @param string $orderBy ORDER BY 语法 e.g. 'id DESC'
     * @param array $limit LIMIT 语法 e.g. LIMIT 25 即 [25]; LIMIT 0, 25 即 [0, 25]
     * @return array 无记录时返回空数组 []
     */
    public function selectAll(string $columns, array $where, string $table = '', string $orderBy = '', array $limit = [])
    {
        list($placeholder, $binds) = $this->parseWhere($where);
        if (empty($placeholder)) {
            return [];
        }

        $table = $this->parseTable($table);
        $orderBy = $this->parseOrderBy($orderBy);
        $limit = $this->parseLimit($limit);
        $sql = "SELECT {$columns} FROM {$table} WHERE {$placeholder} {$orderBy} {$limit}";
        $result = $this->getAll($sql, $binds);

        return $result;
    }

    /**
     * 插入记录
     * @param array $data 要插入的数据 ['col0' => 1]
     * <p>value 使用原生 sql 时，应放在数组里 e.g. ['col0' => ['UNIX_TIMESTAMP()']]</p>
     * <p>支持单条 [...]; 或多条 [[...], [...]]</p>
     * @param string $table 表名，分表情况下允许为空(自动切换为分表 $table)
     * @param bool $ignore 是否使用 INSERT IGNORE 语法
     * @return int 返回最后插入的主键ID(批量插入时返回第1个ID), 失败时返回0
     */
    public function insert(array $data, string $table = '', $ignore = false)
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

                if (isset($v[0]) && is_array($v)) {
                    if (isset($v[1])) { // 参数绑定
                        if (is_array($v[1])) { // ['inc', [1]]
                            $binds = array_merge($binds, $v[1]);
                        } else { // ['inc', 1]
                            $binds = array_merge($binds, array_slice($v, 1));
                        }
                    }

                    $rowValues[] = $v[0]; // 不加引号
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
     * @param string $table 表名，分表情况下允许为空(自动切换为分表 $table)
     * @return int 被更新的行数，否则返回0(<b>注意：不要随便用来当判断条件</b>)
     */
    public function update(array $data, array $where, string $table = '')
    {
        list($placeholder, $binds) = $this->parseWhere($where);
        if (empty($placeholder)) {
            return 0;
        }

        $set = [];
        $setBinds = [];
        foreach ($data as $k => $v) {
            if (isset($v[0]) && is_array($v)) {
                if (isset($v[1])) { // 参数绑定
                    if (is_array($v[1])) { // ['inc', [1]]
                        $setBinds = array_merge($setBinds, $v[1]);
                    } else { // ['inc', 1]
                        $setBinds = array_merge($setBinds, array_slice($v, 1));
                    }
                }

                $v = $v[0]; // 不加引号
            } else {
                $v = "'{$v}'";
            }

            $set[] = '`' . trim($k) . "`={$v}";
        }
        $sqlSet = implode(',', $set);

        $table = $this->parseTable($table);
        $sql = "UPDATE {$table} SET {$sqlSet} WHERE {$placeholder}";
        $result = $this->query($sql, array_merge($setBinds, $binds));

        return $result;
    }

    /**
     * 删除记录
     * @param array $where WHERE 条件
     * <p>KV: $where = ['col0' => 'foo']; 仅支持 AND 逻辑</p>
     * <p>参数绑定: $where = ['col0=?', ['foo']]; $where = ['col0=:c', ['c' => 'foo']]</p>
     * @param string $table 表名，分表情况下允许为空(自动切换为分表 $table)
     * @return int 被删除的行数，否则返回0(<b>注意：不要随便用来当判断条件</b>)
     */
    public function delete(array $where, string $table = '')
    {
        list($placeholder, $binds) = $this->parseWhere($where);
        if (empty($placeholder)) {
            return 0;
        }

        $table = $this->parseTable($table);
        $sql = "DELETE FROM {$table} WHERE {$placeholder}";
        $result = $this->query($sql, $binds);

        return $result;
    }

}
