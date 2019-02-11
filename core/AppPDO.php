<?php

namespace Core;

use PDO;

/**
 * PDO二次封装<br>
 * 支持主从切换
 * @package Core
 */
class AppPDO
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
     * @var string 表名
     */
    private $table = '';

    /**
     * @var array LIMIT语句
     */
    private $limit = '';

    /**
     * @var string 追加的 SQL语句
     */
    private $append = '';

    private function __construct()
    {
    }

    public function __destruct()
    {
        $this->masterConn = null;
        $this->slaveConn = null;
        $this->sectionConn = [];
    }

    /**
     * 对象实例
     * @param array $conf 数据库配置，格式 config/dev/database.php
     * @return PDO|AppPDO
     */
    static function instance(array $conf)
    {
        $instance = new static();
        $instance->conf = $conf;

        return $instance;
    }

    /**
     * 创建连接
     * @param array $host
     * @param string $user
     * @param string $passwd
     * @param string $dbname
     * @return PDO
     */
    protected function initConnection(array $host, string $user, string $passwd, string $dbname = '')
    {
        $dbnameDsn = $dbname ? "dbname={$dbname};" : '';

        $pdo = new PDO("mysql:{$dbnameDsn}host={$host['host']};port={$host['port']}", $user, $passwd,
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
        $isSlave = !$this->isForceMaster
            && in_array($name, ['query', 'prepare', 'exec'])
            && strpos(strtolower($arguments[0]), 'select') !== false;

        // 默认区
        if (!$this->section) {
            if ($isSlave && !empty($this->conf['hosts']['slaves'])) { // elect 使用从库(有 slave 配置的情况下)
                if (!$this->slaveConn) {
                    $slave = $this->conf['hosts']['slaves'][mt_rand(0, count($this->conf['hosts']['slaves']) - 1)];
                    $this->slaveConn = $this->initConnection($slave, $this->conf['user'], $this->conf['passwd'], $this->conf['dbname'] ?? '');
                }
                $pdo = $this->slaveConn;

            } else { // 其它查询使用主库
                if (!$this->masterConn) {
                    $master = $this->conf['hosts']['master'];
                    $this->masterConn = $this->initConnection($master, $this->conf['user'], $this->conf['passwd'], $this->conf['dbname'] ?? '');
                }
                $pdo = $this->masterConn;
            }

        } else { // 扩展区
            $sectionConf = &$this->conf['sections'][$this->section];

            if ($isSlave && !empty($sectionConf['hosts']['slaves'])) { // select 使用从库(有 slave 配置的情况下)
                $sectionConn = &$this->sectionConn[$this->section]['slave'];
                if (empty($sectionConn)) {
                    $slave = $sectionConf['hosts']['slaves'][mt_rand(0, count($sectionConf['hosts']['slaves']) - 1)];
                    $sectionConn = $this->initConnection($slave, $sectionConf['user'], $sectionConf['passwd'], $sectionConf['dbname'] ?? '');
                }

                $pdo = $sectionConn;
            } else { // 其它查询使用主库
                $sectionConn = &$this->sectionConn[$this->section]['master'];
                if (empty($sectionConn)) {
                    $master = $sectionConf['hosts']['master'];
                    $sectionConn = $this->initConnection($master, $sectionConf['user'], $sectionConf['passwd'], $sectionConf['dbname'] ?? '');
                }

                $pdo = $sectionConn;
            }
        }

        $this->section = ''; // 自动重置为默认分区
        $this->isForceMaster = false; // 使用完后自动切换为非强制
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
     * @param string $column 列名
     * @param string|array|null $where 条件，格式看下面
     * @return false|string
     * @see AppPDO::parseWhere() 参考 $where 参数
     */
    public function selectColumn(string $column, $where)
    {
        $table = $this->getTable();
        $where = $this->parseWhere($where);
        $append = $this->getAppend();
        $column = $this->quoteColumn($column);

        $sql = "SELECT {$column} FROM {$table} {$where[0]} {$append} LIMIT 1";

        if (count($where) == 1) {
            /* @var PDO $this */
            return $this->query($sql)->fetchColumn();
        } else {
            /* @var PDO $this */
            $statement = $this->prepare($sql);
            $statement->execute($where[1] ?? null);

            return $statement->fetchColumn();
        }
    }

    /**
     * 查询1行
     * @param string|array|null $where 条件，格式看下面
     * @return false|array
     * @see AppPDO::parseWhere() 参考 $where 参数
     */
    public function selectOne($where)
    {
        $table = $this->getTable();
        $where = $this->parseWhere($where);
        $append = $this->getAppend();

        $sql = "SELECT * FROM {$table} {$where[0]} {$append} LIMIT 1";

        if (count($where) == 1) {
            /* @var PDO $this */
            return $this->query($sql)->fetch(PDO::FETCH_ASSOC);
        } else {
            /* @var PDO $this */
            $statement = $this->prepare($sql);
            $statement->execute($where[1] ?? null);

            return $statement->fetch(PDO::FETCH_ASSOC);
        }
    }

    /**
     * 查询多行
     * @param string $columns
     * @param string|array|null $where 条件，格式看下面
     * @return array 失败返回空数组
     * @see AppPDO::parseWhere() 参考 $where 参数
     */
    public function selectAll(string $columns, $where)
    {
        $table = $this->getTable();
        $where = $this->parseWhere($where);
        $columns = $this->quoteColumn($columns);

        $sql = "SELECT {$columns} FROM {$table} {$where[0]}"
            . $this->getAppend()
            . $this->getLimit();

        if (count($where) == 1) {
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

                if (is_array($v) && isset($v['expr'])) { // 表达式
                    $placeholder[] = $v['expr'];
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
            if (is_array($v) && isset($v['expr'])) { // 表达式
                $updatePlaceHolder[] = $this->quote($k) . " = {$v['expr']}";
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
     * 表达式: ['ctime' => ['expr' => 'UNIX_TIMESTAMP()']]<br>
     * 支持单条[...], 或批量 [[...], [...]]<br>
     * 批量插入时这里不限制长度不分批插入，
     * 由具体业务逻辑构造数组的同时控制批次（例如 $i%500==0 其中$i从1开始，或 array_chunk()）<br>
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
     * 字段表达式: ['num' => ['expr' => 'num + 1']]<br>
     * 函数表达式: ['utime' => ['expr' => 'UNIX_TIMESTAMP()']]
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
     * 字段表达式: ['num' => ['expr' => 'num + 1']]<br>
     * 函数表达式: ['utime' => ['expr' => 'UNIX_TIMESTAMP()']]
     * @param string|array|null $where 条件，格式看下面
     * @see AppPDO::parseWhere() 参考 $where 参数
     * @return int 影响行数
     */
    public function update(array $data, $where)
    {
        $table = $this->getTable();
        $where = $this->parseWhere($where);

        $bind = [];
        $placeholder = [];
        $set = [];
        foreach ($data as $k => $v) {
            if (is_array($v) && isset($v['expr'])) { // 表达式
                $placeholder[] = $this->quote($k) . " = {$v['expr']}";
                $setVal = $v['expr'];
            } else { // 普通值
                $placeholder[] = $this->quote($k) . " = ?";
                $bind[] = $v;
                $setVal = $v;
            }

            $set[] = $this->quote($k) . " = {$setVal}";
        }

        if (count($where) == 1) {
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
     */
    public function delete($where)
    {
        $where = $this->parseWhere($where);
        $sql = sprintf('DELETE FROM %s %s %s',
            $this->getTable(),
            $where[0],
            $this->getLimit()
        );

        if (count($where) == 1) {
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
        $sql = 'SELECT ROW_COUNT()';
        return intval($this->forceMaster()->query($sql)->fetchColumn());
    }

    /**
     * 查询总数
     * @param string|array|null $where 条件，格式看下面
     * @return int
     * @see AppPDO::parseWhere() 参考 $where 参数
     */
    public function count($where)
    {
        return intval(db()->selectColumn('COUNT(1)', $where));
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
     * 返回一次性 LIMIT 语句
     * @return string
     */
    protected function getLimit()
    {
        $limit = $this->limit;
        $this->limit = '';

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
     * 绑定命名参数(不支持update): ['name=:name', [':name' => 'super']]<br>
     * @return array
     */
    protected function parseWhere($where)
    {
        if (empty($where)) {
            $wherePam = [''];
        } elseif (is_string($where)) {
            $wherePam[0] = 'WHERE ' . $where;
        } else {
            $wherePam[0] = 'WHERE ' . $where[0];
            if (isset($where[1])) {
                $wherePam[1] = is_array($where[1]) ? $where[1] : array_slice($where, 1);
            }
        }

        return $wherePam;
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
     * 返回一次性 $this->append
     * @return string
     */
    protected function getAppend()
    {
        $append = ' ' . $this->append;
        $this->append = '';

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
     * 返回一次性 $this->table
     * @return string
     * @throws null
     */
    protected function getTable()
    {
        if (empty($this->table)) {
            throw new AppException('请先通过 $this->table(...) 指定表名');
        }

        $table = $this->table;
        $this->table = '';

        if (strpos($table, '`') === false) {
            if (strpos($table, '.') === false) { // 没有显式指定库名
                $table = "`{$table}`";
            } else {
                $dbTable = explode('.', $table);
                $table = "`{$dbTable[0]}`.`{$dbTable[1]}`";
            }
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
     * <p>order,utime -> `order`,`utime`</p>
     * @param string $column
     * @return string
     */
    protected function quoteColumn(string $column)
    {
        $arrColumn = explode(',', $column);
        foreach ($arrColumn as &$v) {
            $v = $this->quote($v);
        }

        return implode(',', $arrColumn);
    }

}