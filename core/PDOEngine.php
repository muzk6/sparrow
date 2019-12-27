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
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );

        var_dump(11111111);
        return $pdo;
    }

    /**
     * 获取数据库连接
     * @param bool $useMaster 是否使用主库
     * @param string $section 数据库区域
     * @return PDO
     */
    public function getConnection(bool $useMaster = false, string $section = 'default')
    {
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

        var_dump($this->sectionConn);
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
     * 开启事务，并返回数据库连接
     * @param bool $useMaster 是否使用主库
     * @param string $section 数据库区域
     * @return AppPDO|PDO
     */
    public function beginTransaction(bool $useMaster = false, string $section = 'default')
    {
        $connection = $this->getConnection($useMaster, $section);
        if (!$connection->inTransaction()) {
            $connection->beginTransaction();
        }

        return new AppPDO($connection, $this->conf['log']);
    }

    /**
     * 前置勾子
     * @param string $sql
     * @return string
     */
    protected function before(string $sql)
    {
        $sql = trim($sql);
        if ($this->conf['log']) {
            logfile('PDOEngine', $sql, 'sql');
        }

        return $sql;
    }

    /**
     * 查询一行记录
     * @param string $sql 原生 sql 语句
     * @param array $binds 防注入的参数绑定
     * @param bool $useMaster 是否使用主库
     * @param string $section 数据库区域
     * @return array|false 无记录时返回 false
     */
    public function selectOne(string $sql, array $binds = [], bool $useMaster = false, string $section = 'default')
    {
        $sql = $this->before($sql);

        $connection = $this->getConnection($useMaster, $section);
        $statement = $connection->prepare($sql);
        $statement->execute($binds);

        return $statement->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * 查询多行记录
     * @param string $sql 原生 sql 语句
     * @param array $binds 防注入的参数绑定
     * @param bool $useMaster 是否使用主库
     * @param string $section 数据库区域
     * @return array 无记录时返回空数组 []
     */
    public function selectAll(string $sql, array $binds = [], bool $useMaster = false, string $section = 'default')
    {
        $sql = $this->before($sql);

        $connection = $this->getConnection($useMaster, $section);
        $statement = $connection->prepare($sql);
        $statement->execute($binds);

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * 插入记录
     * @param string $sql 原生 sql 语句
     * @param array $binds 防注入的参数绑定
     * @param string $section 数据库区域
     * @return int 返回最后插入的主键ID, 失败时返回0
     */
    public function insert(string $sql, array $binds = [], string $section = 'default')
    {
        $sql = $this->before($sql);

        $connection = $this->getConnection(true, $section);
        $statement = $connection->prepare($sql);
        $statement->execute($binds);

        return intval($connection->lastInsertId());
    }

    /**
     * 更新记录
     * @param string $sql 原生 sql 语句
     * @param array $binds 防注入的参数绑定
     * @param string $section 数据库区域
     * @return int 被更新的行数，否则返回0(<b>注意：不要随便用来当判断条件</b>)
     */
    public function update(string $sql, array $binds = [], string $section = 'default')
    {
        $sql = $this->before($sql);

        $connection = $this->getConnection(true, $section);
        $statement = $connection->prepare($sql);
        $statement->execute($binds);

        return $statement->rowCount();
    }

    /**
     * 删除记录
     * @param string $sql 原生 sql 语句
     * @param array $binds 防注入的参数绑定
     * @param string $section 数据库区域
     * @return int 被删除的行数，否则返回0(<b>注意：不要随便用来当判断条件</b>)
     */
    public function delete(string $sql, array $binds = [], string $section = 'default')
    {
        $sql = $this->before($sql);

        $connection = $this->getConnection(true, $section);
        $statement = $connection->prepare($sql);
        $statement->execute($binds);

        return $statement->rowCount();
    }

}
