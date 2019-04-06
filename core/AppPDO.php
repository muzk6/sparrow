<?php

namespace Core;

use PDO;

/**
 * PDO二次封装<br>
 * 支持主从切换
 * @package Core
 */
final class AppPDO
{
    /**
     * @var PDO 主库连接对象
     */
    protected $masterConn;

    /**
     * @var PDO 从库连接对象
     */
    protected $slaveConn;

    /**
     * @var string 分区<br>
     * 空为默认分区
     */
    protected $section = '';

    /**
     * @var array 分区的连接对象集合
     */
    protected $sectionConn = [];

    /**
     * @var array 数据库配置
     */
    protected $conf;

    /**
     * @var bool 是否强制使用主库
     */
    protected $isForceMaster = false;

    /**
     * @var bool 是否使用 SQL_CALC_FOUND_ROWS
     */
    protected $foundRows = false;

    /**
     * @var string 表名
     */
    protected $table = '';

    /**
     * @var array LIMIT语句
     */
    protected $limit = '';

    /**
     * @var string 追加的 SQL语句
     */
    protected $append = '';

    /**
     * @var array|null WHERE条件组
     */
    protected $where = null;

    /**
     * @var string ORDER语句
     */
    protected $order = '';

    private function __construct()
    {
    }

    public function __destruct()
    {
        $this->close();
    }

    /**
     * 对象实例
     * @param array $conf 数据库配置，格式 config/dev/database.php
     * @return PDO|AppPDO
     */
    public static function instance(array $conf)
    {
        $instance = new static();
        $instance->conf = $conf;

        return $instance;
    }

    /**
     * 关闭所有连接资源
     * @return $this
     */
    public function close()
    {
        $this->reset();
        $this->masterConn = null;
        $this->slaveConn = null;
        $this->sectionConn = [];

        return $this;
    }

    /**
     * 创建连接
     * @param array $host
     * @param string $user
     * @param string $passwd
     * @param string $dbname
     * @param string $charset
     * @return PDO
     */
    protected function initConnection(array $host, string $user, string $passwd, string $dbname = '', string $charset = '')
    {
        $dbnameDsn = $dbname ? "dbname={$dbname};" : '';
        $charsetDsn = $charset ? "charset={$charset}" : '';

        $pdo = new PDO("mysql:{$dbnameDsn}host={$host['host']};port={$host['port']};{$charsetDsn}", $user, $passwd,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );

        return $pdo;
    }

    /**
     * 魔术方法自动切换主从
     * @param string $name
     * @param array $arguments
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        $isSqlStatement = in_array($name, ['query', 'prepare', 'exec']);
        if ($isSqlStatement) {
            $arguments[0] = preg_replace('/\s+/m', ' ', trim($arguments[0]));
            empty($this->conf['log']) || logfile('statement', ['name' => $name, 'arguments' => $arguments], '__sql');
        }

        $isSlave = !$this->isForceMaster
            && $isSqlStatement
            && strpos(strtolower($arguments[0]), 'select') === 0;

        if (!$this->section) { // 默认区
            if ($isSlave && !empty($this->conf['hosts']['slaves'])) { // select 使用从库(有 slave 配置的情况下)
                if (!$this->slaveConn) {
                    $slave = $this->conf['hosts']['slaves'][mt_rand(0, count($this->conf['hosts']['slaves']) - 1)];
                    $this->slaveConn = $this->initConnection($slave, $this->conf['user'], $this->conf['passwd'], $this->conf['dbname'] ?? '', $this->conf['charset'] ?? '');
                }
                $pdo = $this->slaveConn;

            } else { // 其它查询使用主库
                if (!$this->masterConn) {
                    $master = $this->conf['hosts']['master'];
                    $this->masterConn = $this->initConnection($master, $this->conf['user'], $this->conf['passwd'], $this->conf['dbname'] ?? '', $this->conf['charset'] ?? '');
                }
                $pdo = $this->masterConn;
            }

        } else { // 扩展区
            $sectionConf = &$this->conf['sections'][$this->section];

            if ($isSlave && !empty($sectionConf['hosts']['slaves'])) { // select 使用从库(有 slave 配置的情况下)
                $sectionConn = &$this->sectionConn[$this->section]['slave'];
                if (empty($sectionConn)) {
                    $slave = $sectionConf['hosts']['slaves'][mt_rand(0, count($sectionConf['hosts']['slaves']) - 1)];
                    $sectionConn = $this->initConnection($slave, $sectionConf['user'], $sectionConf['passwd'], $sectionConf['dbname'] ?? '', $sectionConf['charset'] ?? '');
                }

                $pdo = $sectionConn;
            } else { // 其它查询使用主库
                $sectionConn = &$this->sectionConn[$this->section]['master'];
                if (empty($sectionConn)) {
                    $master = $sectionConf['hosts']['master'];
                    $sectionConn = $this->initConnection($master, $sectionConf['user'], $sectionConf['passwd'], $sectionConf['dbname'] ?? '', $sectionConf['charset'] ?? '');
                }

                $pdo = $sectionConn;
            }
        }

        in_array($name, ['prepare']) || $this->reset();
        return call_user_func_array([$pdo, $name], $arguments);
    }

    /**
     * 下一次强制使用主库<br>
     * 操作完成后自动重置为非强制
     * @return AppPDO|PDO
     */
    public function forceMaster()
    {
        $this->isForceMaster = true;
        return $this;
    }

    /**
     * 查询1行1列
     * @param string|array $column 字段名<br>
     * 字段: 'col1' 或 ['col1']<br>
     * 表达式: ['raw' => 'COUNT(1)']<br>
     * 更多用法参考 AppPDO::quoteColumn()
     * @param string|array|null $where 条件，格式看下面
     * @return false|string
     * @see AppPDO::parseWhere() 参考 $where 参数
     * @see AppPDO::quoteColumn() 参考字段参数
     * @throws AppException
     */
    public function selectColumn($column, $where)
    {
        $table = $this->getTable();
        $where = $this->parseWhere($where);
        $order = $this->getOrder();
        $append = $this->getAppend();
        $column = $this->quoteColumn($column);

        $sql = "SELECT {$column} FROM {$table} {$where[0]} {$order} {$append} LIMIT 1";

        if (empty($where[1])) {
            /* @var PDO $this */
            return $this->query($sql)->fetchColumn();
        } else {
            /* @var PDO $this */
            $statement = $this->prepare($sql);
            $statement->execute($where[1]);

            return $statement->fetchColumn();
        }
    }

    /**
     * 查询是否存在记录
     * @param string|array|null $where 条件，格式看下面
     * @return bool
     * @see AppPDO::parseWhere() 参考 $where 参数
     * @throws AppException
     */
    public function exists($where)
    {
        return boolval($this->selectColumn(['raw' => 1], $where));
    }

    /**
     * 查询1行
     * @param string|array|null $where 条件，格式看下面
     * @return false|array
     * @see AppPDO::parseWhere() 参考 $where 参数
     * @throws AppException
     */
    public function selectOne($where)
    {
        $table = $this->getTable();
        $where = $this->parseWhere($where);
        $order = $this->getOrder();
        $append = $this->getAppend();

        $sql = "SELECT * FROM {$table} {$where[0]} {$order} {$append} LIMIT 1";

        if (empty($where[1])) {
            /* @var PDO $this */
            return $this->query($sql)->fetch(PDO::FETCH_ASSOC);
        } else {
            /* @var PDO $this */
            $statement = $this->prepare($sql);
            $statement->execute($where[1]);

            return $statement->fetch(PDO::FETCH_ASSOC);
        }
    }

    /**
     * 查询多行
     * @param string|array $columns 字段名<br>
     * 字段: 'col1, col2' 或 ['col1', 'col2']<br>
     * 表达式: ['col1', ['raw' => 'COUNT(1)']]<br>
     * 更多用法参考 AppPDO::quoteColumn()
     * @param string|array|null $where 条件，格式看下面
     * @return array 失败返回空数组
     * @see AppPDO::parseWhere() 参考 $where 参数
     * @see AppPDO::quoteColumn() 参考字段参数
     * @throws AppException
     */
    public function selectAll($columns, $where)
    {
        $table = $this->getTable();
        $where = $this->parseWhere($where);
        $columns = $this->quoteColumn($columns);
        $foundRows = $this->foundRows ? 'SQL_CALC_FOUND_ROWS' : '';

        $sql = "SELECT {$foundRows} {$columns} FROM {$table} {$where[0]}"
            . $this->getOrder()
            . $this->getAppend()
            . $this->getLimit();

        if (empty($where[1])) {
            /* @var PDO $this */
            return $this->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        } else {
            /* @var PDO $this */
            $statement = $this->prepare($sql);
            $statement->execute($where[1]);

            return $statement->fetchAll(PDO::FETCH_ASSOC);
        }
    }

    /**
     * 查询多行同时返回记录总数
     * @param string|array $columns 字段名<br>
     * 字段: 'col1, col2' 或 ['col1', 'col2']<br>
     * 表达式: ['col1', ['raw' => 'COUNT(1)']]<br>
     * 更多用法参考 AppPDO::quoteColumn()
     * @param string|array|null $where 条件，格式看下面
     * @return array 失败返回空数组 ['count' => 数量, 'data' => 数据集']
     * @see AppPDO::parseWhere() 参考 $where 参数
     * @see AppPDO::quoteColumn() 参考字段参数
     * @throws AppException
     */
    public function selectCalc($columns, $where)
    {
        $this->foundRows = true;
        $isForceMaster = $this->isForceMaster;
        $data = $this->selectAll($columns, $where);

        $isForceMaster && $this->forceMaster();
        $count = $this->foundRows();

        return ['count' => $count, 'data' => $data];
    }

    /**
     * 查询 FOUND_ROWS()
     * <p>上一个查询必须有使用 SQL_CALC_FOUND_ROWS</p>
     * @return int
     */
    public function foundRows()
    {
        // 因为 ->selectColumn() 需要指定表名，所以这里使用原生SQL
        $sql = 'SELECT FOUND_ROWS()';

        /* @var PDO $this */
        $count = intval($this->query($sql)->fetchColumn());

        return $count;
    }

    /**
     * 多用途插入、替换记录<br>
     * 用法参考 $this->insert
     * @param array $data
     * @param string $op 操作动作 INSERT INTO, INSERT IGNORE, REPLACE INTO
     * @param array $update ON DUPLICATE KEY UPDATE
     * @return int 返回成功插入后的ID
     */
    protected function multiInsert(array $data, $op = 'INSERT INTO', $update = [])
    {
        // 单条插入，一维转多维
        if (!isset($data[0])) {
            $data = [$data];
        }

        $allPlaceHolder = [];
        $columns = [];
        $values = [];
        foreach ($data as $line => $row) {
            $placeholder = [];
            foreach ($row as $k => $v) {
                if ($line === 0) {
                    $columns[] = $this->quote($k);
                }

                if (is_array($v) && isset($v['raw'])) { // 表达式
                    $placeholder[] = $v['raw'];
                } else { // 普通值
                    $placeholder[] = '?';
                    $values[] = $v;
                }
            }

            $allPlaceHolder[] = '(' . implode(',', $placeholder) . ')';
        }

        // on duplicate key update
        $updatePlaceHolder = [];
        foreach ($update as $k => $v) {
            if (is_array($v) && isset($v['raw'])) { // 表达式
                $updatePlaceHolder[] = $this->quote($k) . " = {$v['raw']}";
            } else { // 普通值
                $updatePlaceHolder[] = $this->quote($k) . " = ?";
                $values[] = $v;
            }
        }

        $sql = sprintf('%s %s (%s) VALUES %s %s',
            $op,
            $this->getTable(),
            implode(',', $columns),
            implode(',', $allPlaceHolder),
            $updatePlaceHolder
                ? 'ON DUPLICATE KEY UPDATE ' . implode(',', $updatePlaceHolder)
                : ''
        );

        // 记住当前 section, 查询上次插入的 ID 用
        $section = $this->section;

        /* @var PDO $this */
        $statement = $this->prepare($sql);
        $statement->execute($values);

        return intval($this->section($section)->lastInsertId());
    }

    /**
     * 插入记录<br>
     * @param array $data 要插入的数据 ['column' => 1]<br>
     * 表达式: ['ctime' => ['raw' => 'UNIX_TIMESTAMP()']]<br>
     * 支持单条[...], 或批量 [[...], [...]]<br>
     * 批量插入时这里不限制长度不分批插入，
     * 由具体业务逻辑构造数组的同时控制批次（例如 $i%500===0 其中$i从1开始，或 array_chunk()，每执行完一次插入后记得重置$data）<br>
     * 强烈建议在分批次插入时开启事务<br>
     * 批量插入时，lastInsertId 是这一批次的第一条记录的ID
     * @return int 返回成功插入后的ID<br>
     * 批量时返回第一条记录的ID
     */
    public function insert(array $data)
    {
        return $this->multiInsert($data);
    }

    /**
     * 插入记录，重复时忽略(跳过)<br>
     * @see AppPDO::insert()
     * @param array $data 要插入的数据<br>
     * 详情参考 AppPDO::insert()
     * @return int 返回成功插入后的ID<br>
     * 批量时返回第一条成功插入记录的ID<br>
     * 忽略时返回0
     */
    public function insertIgnore(array $data)
    {
        return $this->multiInsert($data, 'INSERT IGNORE');
    }

    /**
     * 插入记录，重复时覆盖<br>
     * @see AppPDO::insert()
     * @param array $data 要插入的数据<br>
     * 详情参考 AppPDO::insert()
     * @return int 返回成功插入后的ID<br>
     * 批量时返回第一条记录的ID
     */
    public function replace(array $data)
    {
        return $this->multiInsert($data, 'REPLACE INTO');
    }

    /**
     * 插入遇到主键或唯一索引记录时进行更新<br>
     * INSERT INTO ... ON DUPLICATE KEY UPDATE ...<br>
     * @see AppPDO::insert()
     * @param array $data 要插入的数据<br>
     * 详情参考 AppPDO::insert()
     * @param array $update 要更新的字段 ['column' => 1]<br>
     * 字段表达式: ['num' => ['raw' => 'num + 1']]<br>
     * 函数表达式: ['utime' => ['raw' => 'UNIX_TIMESTAMP()']]
     * @return int 返回成功插入或更新后记录的ID<br>
     * 批量时返回最后一条记录的ID
     */
    public function insertUpdate(array $data, array $update)
    {
        return $this->multiInsert($data, 'INSERT INTO', $update);
    }

    /**
     * 更新记录<br>
     * @param array $data 要更新的字段 ['column' => 1]<br>
     * 字段表达式: ['num' => ['raw' => 'num + 1']]<br>
     * 函数表达式: ['utime' => ['raw' => 'UNIX_TIMESTAMP()']]
     * @param string|array|null $where 条件，格式看下面
     * @see AppPDO::parseWhere() 参考 $where 参数
     * @return int 影响行数
     * @throws AppException
     */
    public function update(array $data, $where)
    {
        $table = $this->getTable();
        $where = $this->parseWhere($where);

        $bind = [];
        $placeholder = [];
        $set = [];
        foreach ($data as $k => $v) {
            if (is_array($v) && isset($v['raw'])) { // 表达式
                $placeholder[] = $this->quote($k) . " = {$v['raw']}";
                $setVal = $v['raw'];
            } else { // 普通值
                $placeholder[] = $this->quote($k) . " = ?";
                $bind[] = $v;
                $setVal = $v;
            }

            $set[] = $this->quote($k) . " = {$setVal}";
        }

        if (empty($where[1])) {
            $sql = sprintf('UPDATE %s SET %s %s %s',
                $table,
                implode(',', $set),
                $where[0],
                $this->getLimit()
            );

            /* @var PDO $this */
            return $this->query($sql)->rowCount();
        } else {
            $sql = sprintf('UPDATE %s SET %s %s %s',
                $table,
                implode(',', $placeholder),
                $where[0],
                $this->getLimit()
            );

            /* @var PDO $this */
            $statement = $this->prepare($sql);
            $statement->execute(array_merge($bind, $where[1]));

            return $statement->rowCount();
        }
    }

    /**
     * 删除记录
     * @param string|array|null $where 条件，格式看下面
     * @see AppPDO::parseWhere() 参考 $where 参数
     * @return int 影响行数
     * @throws AppException
     */
    public function delete($where)
    {
        $where = $this->parseWhere($where);
        $sql = sprintf('DELETE FROM %s %s %s',
            $this->getTable(),
            $where[0],
            $this->getLimit()
        );

        if (empty($where[1])) {
            /* @var PDO $this */
            return $this->query($sql)->rowCount();
        } else {
            /* @var PDO $this */
            $statement = $this->prepare($sql);
            $statement->execute($where[1]);

            return $statement->rowCount();
        }
    }

    /**
     * 上一次查询的影响行数
     * @return int
     */
    public function affectedRows()
    {
        // 因为 ->selectColumn() 需要指定表名，所以这里使用原生SQL
        $sql = 'SELECT ROW_COUNT()';
        return intval($this->forceMaster()->query($sql)->fetchColumn());
    }

    /**
     * 查询总数
     * @param string|array|null $where 条件，格式看下面
     * @return int
     * @see AppPDO::parseWhere() 参考 $where 参数
     * @throws AppException
     */
    public function count($where)
    {
        return intval(db()->selectColumn(['raw' => 'COUNT(1)'], $where));
    }

    /**
     * 为下一个查询构造 LIMIT 语句<br>
     * LIMIT 10: limit(10) 或 limit([10])<br>
     * LIMIT 10, 20: limit(10, 20) 或 limit([10, 20])
     * @param int|array ...$limit
     * @return AppPDO
     */
    public function limit(...$limit)
    {
        $this->limit = $limit;
        return $this;
    }

    /**
     * 以分页格式为下一个查询构造 LIMIT 语句
     * @param int $page 页码
     * @param int $size 每页数量
     * @return AppPDO
     */
    public function page(int $page, int $size)
    {
        return $this->limit(($page - 1) * $size, $size);
    }

    /**
     * ORDER BY
     * @param string|array $columns 字段名<br>
     * 字段: 'col1, col2 desc' 或 ['col1', 'col2 desc']<br>
     * 表达式: [['raw' => '1']]<br>
     * 更多用法参考 AppPDO::quoteColumn()
     * @return $this
     */
    public function orderBy($columns)
    {
        $arrColumn = is_string($columns)
            ? explode(',', $columns)
            : (isset($columns[0]) ? $columns : [$columns]);

        $order = [];
        foreach ($arrColumn as $v) {
            if (isset($v['raw'])) {
                $order[] = $v['raw'];
            } else {
                $v = trim($v);
                if (preg_match('/(asc|desc)/i', $v)) {
                    $withSort = explode(' ', trim($v));
                    $order[] = $this->quote($withSort[0]) . ' ' . strtoupper(end($withSort));
                } else {
                    $order[] = $this->quote($v) . ' ASC';
                }
            }
        }

        $this->order = $order ? ('ORDER BY ' . implode(',', $order)) : '';
        return $this;
    }

    /**
     * 返回 ORDER 语句
     * @return string
     */
    protected function getOrder()
    {
        return ' ' . $this->order;
    }

    /**
     * 返回 LIMIT 语句
     * @return string
     */
    public function getLimit()
    {
        $limit = $this->limit;
        if ($limit) {
            is_array($limit[0]) && $limit = $limit[0];
            $limit = ' LIMIT ' . implode(',', $limit);
        }

        return $limit;
    }

    /**
     * 解析构造 WHERE 参数
     * @param string|array|null $where 条件语句, 取消条件使用 null<br>
     * 无绑定参数: 'id=1' 或 ['id=1']<br>
     * 绑定匿名参数: ['name=?', 'super'] 或 ['name=?', ['super']]<br>
     * 绑定命名参数(不支持update): ['name=:name', [':name' => 'super']] 或去掉后面的冒号 ['name=:name', ['name' => 'super']]<br>
     * @return array eg. ['name=?', ['foo']]; ['', null]
     * @throws AppException
     */
    protected function parseWhere($where)
    {
        if (empty($where)) {
            if ($this->where) {
                return $this->parseWhere($this->where);
            }

            $wherePam = ['', null];
        } elseif (is_string($where)) {
            $wherePam[0] = ' WHERE ' . $where;
        } else {
            if (!isset($where[0])) {
                panic('"$where 参数格式不正确"');
            }

            $wherePam[0] = ' WHERE ' . $where[0];
            if (isset($where[1])) {
                $wherePam[1] = is_array($where[1]) ? $where[1] : array_slice($where, 1);
            }
        }

        return $wherePam;
    }

    /**
     * 返回参数绑定格式的 WHERE
     * @return array eg. ['name=?', ['foo']]; 没有条件时返回 ['']
     * @throws AppException
     */
    public function getWhere()
    {
        return $this->parseWhere(null);
    }

    /**
     * 逻辑条件
     * @param string $logic AND, OR
     * @param string $statement
     * @param array $parameters
     * @see AppPDO::where()
     * @return $this
     */
    protected function logicWhere(string $logic, string $statement, $parameters)
    {
        $this->where || $this->where = ['', []];
        $this->where[0] .= ($this->where[0] ? " {$logic} {$statement}" : $statement);

        if ($parameters) {
            foreach ($parameters as $parameter) {
                if (is_array($parameter)) {
                    $this->where[1] = array_merge($this->where[1], $parameter);
                } else {
                    $this->where[1][] = $parameter;
                }
            }
        }

        return $this;
    }

    /**
     * WHERE ...AND...
     * <p>带有 $where 的查询中，$where=null 时才有效</p>
     * <p>支持多个 ->where()->where()</p>
     * <p>用法与 parseWhere 相同</p>
     * @param string $statement SQL语句 即 parseWhere() 的 $where[0]
     * @param array $parameters 需要绑定参数值 即parseWhere() 的 $where[1]
     * @see AppPDO::parseWhere()
     * @return AppPDO
     */
    public function where(string $statement, ...$parameters)
    {
        return $this->logicWhere('AND', $statement, $parameters);
    }

    /**
     * WHERE ...OR...
     * <p>带有 $where 的查询中，$where=null 时才有效</p>
     * <p>支持多个 ->orWhere()->orWhere()</p>
     * <p>用法与 parseWhere 相同</p>
     * @param string $statement SQL语句 即 parseWhere() 的 $where[0]
     * @param array $parameters 需要绑定参数值 即parseWhere() 的 $where[1]
     * @see AppPDO::parseWhere()
     * @return AppPDO
     */
    public function orWhere(string $statement, ...$parameters)
    {
        return $this->logicWhere('OR', $statement, $parameters);
    }

    /**
     * 在后面追加 SQL 语句<br>
     * 支持任何语句 ORDER BY, GROUP BY, HAVING 等等
     * @param string $sql
     * @return AppPDO
     */
    public function append(string $sql)
    {
        $this->append = $sql;
        return $this;
    }

    /**
     * $this->append
     * @return string
     */
    protected function getAppend()
    {
        $append = ' ' . $this->append;
        return $append;
    }

    /**
     * 一次性切换分区<br>
     * 查询完成后自动切换回默认分区
     * @param string $name
     * @return AppPDO|PDO
     */
    public function section(string $name)
    {
        $this->section = $name;
        return $this;
    }

    /**
     * 一次性切换表(切换库可以显式指定数据库)<br>
     * 查询完成后自动重置为空
     * @param string $table 完整表名
     * @return AppPDO
     */
    public function table(string $table)
    {
        $this->table = $table;
        return $this;
    }

    /**
     * 返回带反引号的表名(支持指定数据库)<br>
     * <p>table -> `table`</p>
     * <p>database.table -> `database`.`table`</p>
     * @return string
     * @throws null
     */
    protected function getTable()
    {
        if (empty($this->table)) {
            throw new AppException('请先通过 $this->table(...) 指定表名');
        }

        $table = $this->table;
        if (strpos($table, '.') === false) { // 没有显式指定库名
            $table = $this->quote($table);
        } else {
            $dbTable = explode('.', $table);
            $table = $this->quote($dbTable[0]) . '.' . $this->quote($dbTable[1]);
        }

        return $table;
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
     * 反引号处理字段<br>
     * <p>'order,utime' -> '`order`,`utime`'</p>
     * <p>['order','utime'] -> '`order`,`utime`'</p>
     * <p>['order'] -> '`order`'</p>
     * <p>['order', ['raw' => 'COUNT(1)']] -> '`order`, COUNT(1)'</p>
     * <p>['raw' => 'COUNT(1)'] -> 'COUNT(1)'</p>
     * @param string|array $column
     * @return string
     */
    protected function quoteColumn($column)
    {
        $arrColumn = is_string($column)
            ? explode(',', $column)
            : (isset($column[0]) ? $column : [$column]);
        foreach ($arrColumn as &$v) {
            $v = $v['raw'] ?? $this->quote($v);
        }

        return implode(',', $arrColumn);
    }

    /**
     * 参数重置
     */
    protected function reset()
    {
        $this->section = ''; // 重置为默认分区
        $this->isForceMaster = false; // 使用完后自动切换为非强制
        $this->foundRows = false; // 重置为不使用 SQL_CALC_FOUND_ROWS
        $this->table = ''; // 重置表名
        $this->limit = ''; // 重置LIMIT
        $this->append = ''; // 重置附加语句
        $this->where = null; // 重置WHERE
    }

}
