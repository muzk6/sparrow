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
     * @var array 数据库配置
     */
    protected $conf;

    /**
     * @var bool 是否强制使用主库
     */
    protected $isForceMaster = false;

    /**
     * @var string LIMIT语句
     */
    private $limit = '';

    /**
     * @var array WHERE参数
     */
    private $where = [];

    /**
     * @var bool 是否不强制使用 WHERE语句
     */
    private $isNoWhere = false;

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
     * @param $host
     * @return PDO
     */
    private function initConnection(array $host)
    {
        $pdo = new PDO("mysql:dbname={$this->conf['dbname']};host={$host['host']};port={$host['port']}",
            $this->conf['user'], $this->conf['passwd'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

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
        // select 使用从库
        if (!$this->isForceMaster
            && in_array($name, ['query', 'prepare', 'exec'])
            && strpos(strtolower($arguments[0]), 'select') !== false) {

            if (!$this->slaveConn) {
                $slave = $this->conf['hosts']['slaves'][mt_rand(0, count($this->conf['hosts']['slaves']) - 1)];
                $this->slaveConn = $this->initConnection($slave);
            }
            $pdo = $this->slaveConn;

        } else { // 其它查询使用主库
            if (!$this->masterConn) {
                $master = $this->conf['hosts']['master'];
                $this->masterConn = $this->initConnection($master);
            }
            $pdo = $this->masterConn;
        }

        $this->isForceMaster = false; // 使用完后自动切换为非强制
        return call_user_func_array([$pdo, $name], $arguments);
    }

    /**
     * 下一次强制使用主库<br>
     * 操作完成后自动重置为非强制
     * @return PDO|static
     */
    public function forceMaster()
    {
        $this->isForceMaster = true;
        return $this;
    }

    /**
     * 查询1行1列
     * @param string $table
     * @param string $column
     * @return false|string
     */
    public function selectColumn(string $table, string $column)
    {
        $where = $this->getWhere();
        $sql = "SELECT {$column} FROM {$table} {$where[0]}" . $this->getAppend();

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
     * @param string $table
     * @return false|array
     */
    public function selectOne(string $table)
    {
        $where = $this->getWhere();
        $sql = "SELECT * FROM {$table} {$where[0]}" . $this->getAppend();

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
     * @param string $table
     * @param string $columns
     * @return array 失败返回空数组
     */
    public function selectAll(string $table, string $columns)
    {
        $where = $this->getWhere();
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
     * 用法参考 public 的 insert
     * @param string $table
     * @param array $data
     * @param string $op 操作动作 INSERT INTO, INSERT IGNORE, REPLACE INTO
     * @return bool
     */
    private function multiInsert(string $table, array $data, $op = 'INSERT INTO')
    {
        // 单条插入，一维转多维
        if (count($data) == count($data, COUNT_RECURSIVE)) {
            $data = [$data];
        }

        $allPlaceHolder = [];
        $columns = [];
        $values = [];
        foreach ($data as $line => $row) {
            $placeholder = [];
            foreach ($row as $k => $v) {
                if ($line === 0) {
                    $columns[] = "`{$k}`";
                }

                $placeholder[] = '?';
                $values[] = $v;
            }

            $allPlaceHolder[] = '(' . implode(',', $placeholder) . ')';
        }

        $sql = sprintf('%s `%s` (%s) VALUES %s',
            $op,
            $table,
            implode(',', $columns),
            implode(',', $allPlaceHolder)
        );

        /* @var PDO $this */
        $statement = $this->prepare($sql);
        return $statement->execute($values);
    }

    /**
     * 插入记录<br>
     * <i>注意：为降低学习成本不支持 <b>ON DUPLICATE KEY UPDATE</b></i>
     * @param string $table
     * @param array $data 支持单条[...], 或批量 [[...], [...]]<br>
     * 批量插入时这里不限制长度不分批插入，
     * 由具体业务逻辑构造数组的同时控制批次（例如 $i%500==0 其中$i从1开始，或 array_chunk()）<br>
     * 强烈建议在分批次插入时开启事务<br>
     * 批量插入时，lastInsertId 是这一批次的第一条记录的ID
     * @return bool
     */
    public function insert(string $table, array $data)
    {
        return $this->multiInsert($table, $data);
    }

    /**
     * 插入记录，重复时忽略<br>
     * 用法参考 insert
     * @param string $table
     * @param array $data
     * @return bool
     */
    public function insertIgnore(string $table, array $data)
    {
        return $this->multiInsert($table, $data, 'INSERT IGNORE');
    }

    /**
     * 插入记录，重复时覆盖<br>
     * 用法参考 insert
     * @param string $table
     * @param array $data
     * @return bool
     */
    public function replace(string $table, array $data)
    {
        return $this->multiInsert($table, $data, 'REPLACE INTO');
    }

    /**
     * 更新记录<br>
     * <i>注意：不支持 <b>['num' => 'num+1']</b> 的写法，请用原生 sql 实现</i>
     * @param string $table
     * @param array $data
     * @return int 影响行数
     * @throws AppException
     */
    public function update(string $table, array $data)
    {
        $where = $this->getWhere();

        $bind = [];
        $placeholder = [];
        $set = [];
        foreach ($data as $k => $v) {
            $placeholder[] = "`{$k}` = ?";
            $bind[] = $v;

            $set[] = "`{$k}` = {$v}";
        }

        if (count($where) == 1) {
            if (!$this->isNoWhere() && empty($where[0])) {
                throw new AppException('缺少 WHERE 条件, 此前必须先调用 where(...), 更新全部用 where(null)');
            }

            $sql = sprintf('UPDATE `%s` SET %s %s %s',
                $table,
                implode(',', $set),
                $where[0],
                $this->getLimit()
            );

            /* @var PDO $this */
            return $this->query($sql)->rowCount();
        } else {
            $sql = sprintf('UPDATE `%s` SET %s %s %s',
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
     * @param string $table
     * @return int 影响行数
     * @throws AppException
     */
    public function delete(string $table)
    {
        $where = $this->getWhere();
        $sql = sprintf('DELETE FROM `%s` %s %s',
            $table,
            $where[0],
            $this->getLimit()
        );

        if (count($where) == 1) {
            if (!$this->isNoWhere() && empty($where[0])) {
                throw new AppException('缺少 WHERE 条件, 此前必须先调用 where(...), 删除全部用 where(null)');
            }

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
        /* @var PDO $this */

        $sql = 'SELECT ROW_COUNT()';
        return intval($this->forceMaster()->query($sql)->fetchColumn());
    }

    /**
     * 查询总数
     * @param string $table
     * @return int
     */
    public function count(string $table)
    {
        return db()->selectColumn($table, 'COUNT(1)');
    }

    /**
     * 为下一个查询构造 LIMIT 语句<br>
     * LIMIT 10: limit(10)<br>
     * LIMIT 10, 20: limit(10, 20)
     * @param int ...$limit
     * @return PDO|static
     */
    public function limit(int ...$limit)
    {
        $this->limit = ' LIMIT ' . implode(',', $limit);
        return $this;
    }

    /**
     * 以分页格式为下一个查询构造 LIMIT 语句
     * @param int $page 页码
     * @param int $size 每页数量
     * @return AppPDO|PDO
     */
    public function page(int $page, int $size)
    {
        return $this->limit(($page - 1) * $size, $size);
    }

    /**
     * 返回一次性 LIMIT 语句
     * @return string
     */
    private function getLimit()
    {
        $limit = $this->limit;
        $this->limit = '';

        return $limit;
    }

    /**
     * 为下一个查询准备 WHERE 参数
     * @param array|string|null $where 条件语句<br>
     * 绑定匿名参数: where('name=?', ['super'])<br>
     * 绑定命名参数(不支持update): where('name=:name', [':name' => 'super'])<br>
     * 无绑定参数: where('id=1')<br>
     * 取消强制WHERE(适用于update,delete): where(null)
     * @return PDO|static
     */
    public function where(...$where)
    {
        if ($where[0] === null) {
            $this->where = [];
            $this->isNoWhere = true;
        } else {
            $this->where[0] = 'WHERE ' . $where[0];
            isset($where[1]) && $this->where[1] = $where[1];
        }

        return $this;
    }

    /**
     * 返回一次性 WHERE 参数
     * @return array
     */
    private function getWhere()
    {
        $where = $this->where ?: [''];
        $this->where = [];

        return $where;
    }

    /**
     * 返回一次性 $this->isNoWhere
     * @return bool
     */
    private function isNoWhere()
    {
        $isNoWhere = $this->isNoWhere;
        $this->isNoWhere = false;

        return $isNoWhere;
    }

    /**
     * 在后面追加 SQL 语句<br>
     * 支持任何语句 ORDER BY, GROUP BY, HAVING 等等
     * @param string $sql
     * @return PDO|static
     */
    public function append(string $sql)
    {
        $this->append = ' ' . $sql;
        return $this;
    }

    /**
     * 返回一次性 $this->append
     * @return string
     */
    private function getAppend()
    {
        $append = $this->append;
        $this->append = '';

        return $append;
    }

}