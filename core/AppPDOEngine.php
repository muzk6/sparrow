<?php


namespace Core;


use PDO;

class AppPDOEngine
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
     * @param array $conf 数据库配置，格式 config/dev/database.php
     */
    public function __construct(array $conf)
    {
        $this->conf = $conf;
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
     * 关闭所有连接资源
     * @return $this
     */
    public function close()
    {
        $this->masterConn = null;
        $this->slaveConn = null;
        $this->sectionConn = [];

        return $this;
    }

}