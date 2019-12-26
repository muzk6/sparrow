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
     * @var array 所有分区的连接对象集合
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
     * 关闭所有连接资源
     * @return $this
     */
    public function close()
    {
        $this->sectionConn = [];
        return $this;
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

        return $pdo;
    }

    /**
     * 获取数据库连接
     * @param bool $useMaster
     * @param string $section 分区
     * @return PDO
     */
    public function getConnection(bool $useMaster = false, string $section = 'default')
    {
        $sectionConf = &$this->conf['sections'][$section];

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

    protected function log($sql)
    {
        if ($this->conf['log']) {
            logfile('statement', $sql, 'sql');
        }
    }

    /**
     * 前置勾子
     * @param string $sql
     * @return string
     */
    protected function before(string $sql)
    {
        $sql = trim($sql);
        $this->log($sql);

        return $sql;
    }

    /**
     * @param string $sql
     * @param array $binds
     * @param bool $useMaster
     * @param array $sharding
     * @return array|false
     */
    public function selectOne(string $sql, array $binds = [], bool $useMaster = false, array $sharding = [])
    {
        $sql = $this->before($sql);

        if (!empty($sharding)) {
            foreach ($sharding as $class => $index) {
                /** @var \Core\BaseModel $model */
                $model = app($class);
                $model->sharding($index);
                
            }
        }

        $connection = $this->getConnection($useMaster);
        $statement = $connection->prepare($sql);
        $statement->execute($binds);

        return $statement->fetch(PDO::FETCH_ASSOC);
    }

}
