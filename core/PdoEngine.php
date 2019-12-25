<?php


namespace Core;


use PDO;

/**
 * PDO引擎，管理连接资源、执行查询
 * @package Core
 */
class PdoEngine
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
     * @var string 连接资源的分区名，空为默认分区
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
     * 参数重置
     */
    public function reset()
    {
        $this->section = ''; // 重置为默认分区
        $this->isForceMaster = false; // 使用完后自动切换为非强制
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

    public function getPDO(string $cmd, array $arguments, bool $useMaster = false, string $section = 'default')
    {
        $isSqlStatement = in_array($cmd, ['query', 'prepare', 'exec']);
        if ($isSqlStatement) {
            $arguments[0] = preg_replace('/\s+/m', ' ', trim($arguments[0]));
            empty($this->conf['log']) || logfile('statement', ['name' => $cmd, 'arguments' => $arguments], 'sql');
        }

        $isSlave = !$useMaster
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
            $sectionConf = &$this->conf['sections'][$section];

            if ($isSlave && !empty($sectionConf['hosts']['slaves'])) { // select 使用从库(有 slave 配置的情况下)
                $sectionConn = &$this->sectionConn[$section]['slave'];
                if (empty($sectionConn)) {
                    $slave = $sectionConf['hosts']['slaves'][mt_rand(0, count($sectionConf['hosts']['slaves']) - 1)];
                    $sectionConn = $this->initConnection($slave, $sectionConf['user'], $sectionConf['passwd'], $sectionConf['dbname'] ?? '', $sectionConf['charset'] ?? '');
                }

                $pdo = $sectionConn;
            } else { // 其它查询使用主库
                $sectionConn = &$sectionConn[$section]['master'];
                if (empty($sectionConn)) {
                    $master = $sectionConf['hosts']['master'];
                    $sectionConn = $this->initConnection($master, $sectionConf['user'], $sectionConf['passwd'], $sectionConf['dbname'] ?? '', $sectionConf['charset'] ?? '');
                }

                $pdo = $sectionConn;
            }
        }

        return $pdo;
    }

}
