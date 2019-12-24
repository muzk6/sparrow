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
     * 用于存放原始元数据，即 sharding 前的 section, database, table
     * @var array
     */
    private $originalMeta = [];

    /**
     * 连接资源引擎
     * @var PdoEngine|PDO
     */
    private $engine;

    /**
     * @var bool 是否使用 SQL_CALC_FOUND_ROWS
     */
    private $withFoundRows = false;

    /**
     * @var array LIMIT语句
     */
    private $limit = '';

    /**
     * @var string 追加的 SQL语句
     */
    private $append = '';

    /**
     * @var array|null WHERE条件组
     */
    private $where = null;

    /**
     * @var string ORDER语句
     */
    private $order = '';

    public function __construct(PdoEngine $pdoEngine)
    {
        $this->engine = $pdoEngine;
        $this->originalMeta = [
            'section' => $this->section,
            'database' => $this->database,
            'table' => $this->table,
        ];
    }

    /**
     * 所有PDO的底层查询都经过这里
     * @param string $name
     * @param array $arguments
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        // 还原回构造对象时的元数据，重置 Model 的 Sharding()
        foreach ($this->originalMeta as $k => $v) {
            $this->$k = $v;
        }

        return call_user_func_array([$this->getEngine(), $name], $arguments);
    }

    /**
     * 返回 PDO 引擎
     * @return PdoEngine|PDO
     */
    public function getEngine()
    {
        $this->engine->setSection($this->section);
        return $this->engine;
    }

    /**
     * 开启手动事务
     * @return $this
     */
    public function beginTransaction()
    {
        $this->getEngine()->beginTransaction();
        return $this;
    }

    /**
     * 提交事务
     * @return $this
     */
    public function commit()
    {
        $this->getEngine()->commit();
        return $this;
    }

    /**
     * 回滚事务
     * @return $this
     */
    public function rollBack()
    {
        $this->getEngine()->rollBack();
        return $this;
    }

    /**
     * 关闭所有连接资源
     * @return $this
     */
    public function close()
    {
        $this->getEngine()->close();
        return $this;
    }

    /**
     * 下一次强制使用主库<br>
     * 操作完成后自动重置为非强制
     * @return static|PDO
     */
    public function forceMaster()
    {
        $this->getEngine()->forceMaster();
        return $this;
    }

    /**
     * 查询1行1列
     * @param string|array $column 字段名<br>
     * 字段: 'col1' 或 ['col1']<br>
     * 表达式: ['raw' => 'COUNT(1)']<br>
     * 更多用法参考 AppPDO::quoteColumn()
     * @return false|string
     * @throws AppException
     * @see AppPDO::quoteColumn() 参考字段参数
     */
    public function selectColumn($column = '*')
    {
        $table = $this->getTable();
        $where = $this->parseWhere();
        $order = $this->getOrder();
        $append = $this->getAppend();
        $column = $this->quoteColumn($column);
        $this->reset();

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
     * @return bool
     * @throws AppException
     */
    public function exists()
    {
        return boolval($this->selectColumn(['raw' => 1]));
    }

    /**
     * 查询1行
     * @param string|array $columns 字段名<br>
     * 字段: 'col1, col2' 或 ['col1', 'col2']<br>
     * 表达式: ['col1', ['raw' => 'COUNT(1)']]<br>
     * 更多用法参考 AppPDO::quoteColumn()
     * @return false|array
     * @throws AppException
     */
    public function selectOne($columns = '*')
    {
        $table = $this->getTable();
        $where = $this->parseWhere();
        $columns = $this->quoteColumn($columns);
        $order = $this->getOrder();
        $append = $this->getAppend();
        $this->reset();

        $sql = "SELECT {$columns} FROM {$table} {$where[0]} {$order} {$append} LIMIT 1";

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
     * @return array 失败返回空数组
     * @throws AppException
     * @see AppPDO::quoteColumn() 参考字段参数
     */
    public function selectAll($columns = '*')
    {
        $table = $this->getTable();
        $where = $this->parseWhere();
        $columns = $this->quoteColumn($columns);
        $foundRows = $this->withFoundRows ? 'SQL_CALC_FOUND_ROWS' : '';

        $sql = "SELECT {$foundRows} {$columns} FROM {$table} {$where[0]}"
            . $this->getOrder()
            . $this->getAppend()
            . $this->getLimit();

        $this->reset();
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
     * @return array 失败返回空数组 ['count' => 数量, 'data' => 数据集']
     * @throws AppException
     * @see AppPDO::quoteColumn() 参考字段参数
     */
    public function selectCalc($columns = '*')
    {
        $this->withFoundRows = true;
        $isForceMaster = $this->getEngine()->getIsForceMaster();
        $data = $this->selectAll($columns);

        $isForceMaster && $this->forceMaster();
        $count = $this->foundRows();
        $this->reset();

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
        $section = $this->getEngine()->getSection();

        /* @var PDO $this */
        $statement = $this->prepare($sql);
        $statement->execute($values);

        $lastInsertId = intval($this->setSection($section)->lastInsertId());
        $this->reset();

        return $lastInsertId;
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
     * @param array $data 要插入的数据<br>
     * 详情参考 AppPDO::insert()
     * @return int 返回成功插入后的ID<br>
     * 批量时返回第一条成功插入记录的ID<br>
     * 忽略时返回0
     * @see AppPDO::insert()
     */
    public function insertIgnore(array $data)
    {
        return $this->multiInsert($data, 'INSERT IGNORE');
    }

    /**
     * 插入记录，重复时覆盖<br>
     * @param array $data 要插入的数据<br>
     * 详情参考 AppPDO::insert()
     * @return int 返回成功插入后的ID<br>
     * 批量时返回第一条记录的ID
     * @see AppPDO::insert()
     */
    public function replace(array $data)
    {
        return $this->multiInsert($data, 'REPLACE INTO');
    }

    /**
     * 插入遇到主键或唯一索引记录时进行更新<br>
     * INSERT INTO ... ON DUPLICATE KEY UPDATE ...<br>
     * @param array $data 要插入的数据<br>
     * 详情参考 AppPDO::insert()
     * @param array $update 要更新的字段 ['column' => 1]<br>
     * 字段表达式: ['num' => ['raw' => 'num + 1']]<br>
     * 函数表达式: ['utime' => ['raw' => 'UNIX_TIMESTAMP()']]
     * @return int 返回成功插入或更新后记录的ID<br>
     * 批量时返回最后一条记录的ID
     * @see AppPDO::insert()
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
     * @return int 影响行数
     * @throws AppException
     */
    public function update(array $data)
    {
        $table = $this->getTable();
        $where = $this->parseWhere();
        if (empty($where[0])) {
            panic('请先通过 $this->where(...) 指定条件');
        }

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

            $this->reset();
            /* @var PDO $this */
            return $this->query($sql)->rowCount();
        } else {
            $sql = sprintf('UPDATE %s SET %s %s %s',
                $table,
                implode(',', $placeholder),
                $where[0],
                $this->getLimit()
            );

            $this->reset();
            /* @var PDO $this */
            $statement = $this->prepare($sql);
            $statement->execute(array_merge($bind, $where[1]));

            return $statement->rowCount();
        }
    }

    /**
     * 删除记录
     * @return int 影响行数
     * @throws AppException
     */
    public function delete()
    {
        $where = $this->parseWhere();
        if (empty($where[0])) {
            panic('请先通过 $this->where(...) 指定条件');
        }

        $sql = sprintf('DELETE FROM %s %s %s',
            $this->getTable(),
            $where[0],
            $this->getLimit()
        );
        $this->reset();

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
     * @return int
     * @throws AppException
     */
    public function count()
    {
        return intval($this->selectColumn(['raw' => 'COUNT(1)']));
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
     * 无绑定参数: 'id=1' 或 ['id=1']<br>
     * 绑定匿名参数: ['name=?', 'super'] 或 ['name=?', ['super']]<br>
     * 绑定命名参数(不支持update): ['name=:name', [':name' => 'super']] 或去掉后面的冒号 ['name=:name', ['name' => 'super']]<br>
     * @return array eg. ['name=?', ['foo']]; ['', null]
     * @throws AppException
     */
    protected function parseWhere()
    {
        $where = $this->where;
        if (empty($where)) {
            $wherePam = ['', null];
        } elseif (is_string($where)) {
            $wherePam[0] = ' WHERE ' . $where;
        } else {
            if (!isset($where[0])) {
                panic('$where 参数格式不正确');
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
     * @return array eg. ['name=?', ['foo']]; ['', null]
     * @throws AppException
     */
    public function getWhere()
    {
        return $this->parseWhere();
    }

    /**
     * 逻辑条件
     * @param string $logic AND, OR
     * @param string $statement
     * @param array $parameters
     * @return $this
     * @see AppPDO::where()
     */
    private function logicWhere(string $logic, string $statement, $parameters)
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
     * <p>支持多个 ->where()->where()</p>
     * <p>用法与 parseWhere 相同</p>
     * @param string $statement SQL语句 即 parseWhere() 的 $where[0]
     * @param array $parameters 需要绑定参数值 即parseWhere() 的 $where[1]
     * @return AppPDO
     * @see AppPDO::parseWhere()
     */
    public function where(string $statement, ...$parameters)
    {
        return $this->logicWhere('AND', $statement, $parameters);
    }

    /**
     * WHERE ...OR...
     * <p>支持多个 ->orWhere()->orWhere()</p>
     * <p>用法与 parseWhere 相同</p>
     * @param string $statement SQL语句 即 parseWhere() 的 $where[0]
     * @param array $parameters 需要绑定参数值 即parseWhere() 的 $where[1]
     * @return AppPDO
     * @see AppPDO::parseWhere()
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
    private function getAppend()
    {
        $append = ' ' . $this->append;
        return $append;
    }

    /**
     * 设置连接资源的分区名
     * @param string $section
     * @return AppPDO|PDO
     */
    public function setSection(string $section)
    {
        $this->section = $section;
        return $this;
    }

    /**
     * 设置数据库名
     * @param string $database
     * @return $this
     */
    public function setDatabase(string $database)
    {
        $this->database = $database;
        return $this;
    }

    /**
     * 设置表名
     * @param string $table 表名
     * @return AppPDO
     */
    public function setTable(string $table)
    {
        $this->table = $table;
        return $this;
    }

    /**
     * 返回带反引号的表名(有设置 $this->database 时返回的表名会带上库名)
     * @return string
     * @throws null
     */
    public function getTable()
    {
        if (empty($this->table)) {
            panic('请先通过 $this->setTable(...) 指定表名');
        }

        if ($this->database) {
            $table = $this->quote($this->database) . '.' . $this->quote($this->table);
        } else {
            $table = $this->quote($this->table);
        }

        return $table;
    }

    /**
     * 反引号修饰处理
     * @param string $name
     * @return string
     */
    private function quote(string $name)
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
    private function quoteColumn($column)
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
    public function reset()
    {
        $this->getEngine()->reset();

        $this->withFoundRows = false; // 重置为不使用 SQL_CALC_FOUND_ROWS
        $this->limit = ''; // 重置LIMIT
        $this->append = ''; // 重置附加语句
        $this->order = ''; // 重置ORDER
        $this->where = null; // 重置WHERE
    }

}
