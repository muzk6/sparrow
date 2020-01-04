<?php

namespace Core;

use PDO;

/**
 * PDO 引擎
 * @package Core
 */
class PDOEngine
{
    /**
     * @var array 数据库配置
     */
    protected $conf;

    /**
     * @var array 所有分区的连接对象集合
     */
    protected $sectionConn = [];

    /**
     * @param array $conf 数据库配置，格式 config/dev/database.php
     */
    public function __construct(array $conf)
    {
        $this->conf = $conf;
    }

    public function __destruct()
    {
        $this->close();
    }

    /**
     * 创建新连接
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
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_EMULATE_PREPARES => false]
        );

        return $pdo;
    }

    /**
     * 获取连接资源 PDO
     * @param bool $useMaster 是否使用主库
     * @param string $section 数据库区域，为空时自动切换为 default
     * @return PDO
     */
    public function getConnection(bool $useMaster = false, string $section = '')
    {
        if (empty($section)) {
            $section = 'default';
        }

        $sectionConf = &$this->conf['sections'][$section];
        if (is_null($sectionConf)) {
            trigger_error("database.php 配置错误，`sections -> {$section}` 不存在");
            exit;
        }

        if ($useMaster || empty($sectionConf['hosts']['slaves'])) { // 主库；在没有配置从库时也使用主库
            $connection = &$this->sectionConn[$section]['master'];
            if (empty($connection)) {
                $connection = $this->initConnection($sectionConf['hosts']['master'],
                    $sectionConf['user'],
                    $sectionConf['passwd'],
                    $sectionConf['dbname'] ?? '',
                    $sectionConf['charset'] ?? ''
                );
            }

        } else { // 从库
            $connection = &$this->sectionConn[$section]['slave'];
            if (empty($connection)) {
                $connection = $this->initConnection($sectionConf['hosts']['slaves'][mt_rand(0, count($sectionConf['hosts']['slaves']) - 1)],
                    $sectionConf['user'],
                    $sectionConf['passwd'],
                    $sectionConf['dbname'] ?? '',
                    $sectionConf['charset'] ?? ''
                );
            }
        }

        return $connection;
    }

    /**
     * 关闭所有连接资源
     * @return void
     */
    public function close()
    {
        foreach ($this->sectionConn as $section => &$connections) {
            foreach ($connections as &$connection) {
                $connection = null;
            }
        }
    }

    /**
     * 开启事务，并返回连接资源
     * @param string $section 数据库区域，为空时自动切换为 default
     * @return AppPDO
     */
    public function beginTransaction(string $section = '')
    {
        $appPDO = new AppPDO($this->getConnection(true, $section), $this->conf['log']);
        $appPDO->beginTransaction();

        return $appPDO;
    }

    /**
     * 分表
     * @param string $table 原始表名
     * @param string $index 分表依据
     * @return PDOSharding
     */
    public function shard(string $table, string $index)
    {
        return new PDOSharding($table, $index, config('sharding'), $this->conf['log']);
    }

    /**
     * 查询一行记录
     * @param string $sql 原生 sql 语句
     * @param array $binds 防注入的参数绑定
     * @param bool $useMaster 是否使用主库
     * @param string $section 数据库区域，为空时自动切换为 default
     * @return array|false 无记录时返回 false
     */
    public function getOne(string $sql, array $binds = [], bool $useMaster = false, string $section = '')
    {
        return (new AppPDO($this->getConnection($useMaster, $section), $this->conf['log']))->getOne($sql, $binds);
    }

    /**
     * 查询多行记录
     * @param string $sql 原生 sql 语句
     * @param array $binds 防注入的参数绑定
     * @param bool $useMaster 是否使用主库
     * @param string $section 数据库区域，为空时自动切换为 default
     * @return array 无记录时返回空数组 []
     */
    public function getAll(string $sql, array $binds = [], bool $useMaster = false, string $section = '')
    {
        return (new AppPDO($this->getConnection($useMaster, $section), $this->conf['log']))->getAll($sql, $binds);
    }

    /**
     * 执行 insert, replace, update, delete 等增删改 sql 语句
     * @param string $sql 原生 sql 语句
     * @param array $binds 防注入的参数绑定
     * @param string $section 数据库区域，为空时自动切换为 default
     * @return int 执行 insert, replace 时返回最后插入的主键ID, 失败时返回0
     * <br>其它语句返回受影响行数，否则返回0(<b>注意：不要随便用来当判断条件</b>)
     */
    public function query(string $sql, array $binds = [], string $section = '')
    {
        return (new AppPDO($this->getConnection(true, $section), $this->conf['log']))->query($sql, $binds);
    }

    /**
     * 插入记录
     * @param array $data 要插入的数据 ['col0' => 1]
     * <p>value 使用原生 sql 时，应放在数组里 e.g. ['col0' => ['UNIX_TIMESTAMP()']]</p>
     * <p>支持单条 [...]; 或多条 [[...], [...]]</p>
     * @param string $table 表名
     * @param bool $ignore 是否使用 INSERT IGNORE 语法
     * @param string $section 数据库区域，为空时自动切换为 default
     * @return int 返回最后插入的主键ID(批量插入时返回第1个ID), 失败时返回0
     */
    public function insert(array $data, string $table, $ignore = false, string $section = '')
    {
        return (new AppPDO($this->getConnection(true, $section), $this->conf['log']))->insert($data, $table, $ignore);
    }

    /**
     * 更新记录
     * @param array $data 要更新的字段 ['col0' => 1]
     * <p>value 使用原生 sql 时，应放在数组里 e.g. ['col0' => ['col0+1']]</p>
     * @param array $where WHERE 条件
     * <p>KV: $where = ['col0' => 'foo']; 仅支持 AND 逻辑</p>
     * <p>参数绑定: $where = ['col0=?', ['foo']]; $where = ['col0=:c', ['c' => 'foo']]</p>
     * @param string $table 表名
     * @param string $section 数据库区域，为空时自动切换为 default
     * @return int 被更新的行数，否则返回0(<b>注意：不要随便用来当判断条件</b>)
     */
    public function update(array $data, array $where, string $table, string $section = '')
    {
        return (new AppPDO($this->getConnection(true, $section), $this->conf['log']))->update($data, $where, $table);
    }

    /**
     * 删除记录
     * @param array $where WHERE 条件
     * <p>KV: $where = ['col0' => 'foo']; 仅支持 AND 逻辑</p>
     * <p>参数绑定: $where = ['col0=?', ['foo']]; $where = ['col0=:c', ['c' => 'foo']]</p>
     * @param string $table 表名
     * @param string $section 数据库区域，为空时自动切换为 default
     * @return int 被删除的行数，否则返回0(<b>注意：不要随便用来当判断条件</b>)
     */
    public function delete(array $where, string $table, string $section = '')
    {
        return (new AppPDO($this->getConnection(true, $section), $this->conf['log']))->delete($where, $table);
    }

}
