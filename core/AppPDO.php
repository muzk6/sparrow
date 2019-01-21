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
    protected $pdoMaster;

    /**
     * @var PDO 从库连接对象
     */
    protected $pdoSlave;

    /**
     * @var array
     */
    protected $conf;

    /**
     * @var bool 是否强制使用主库
     */
    protected $isForceMaster = false;

    private function __construct()
    {
    }

    public function __destruct()
    {
        $this->pdoMaster = null;
        $this->pdoSlave = null;
    }

    /**
     * 取对象实例
     * @param array $conf 数据库配置，格式 config/dev/database.php
     * @return PDO|AppPDO
     */
    static function instance(array $conf)
    {
        $instance = new static();
        $instance->conf = $conf;

        return $instance;
    }

    public function __call($name, $arguments)
    {
        // select 使用从库
        if (!$this->isForceMaster
            && in_array($name, ['query', 'prepare', 'exec'])
            && strpos(strtolower($arguments[0]), 'select') !== false) {

            if (!$this->pdoSlave) {
                $slave = $this->conf['hosts']['slaves'][mt_rand(0, count($this->conf['hosts']['slaves']) - 1)];
                $this->pdoSlave = new PDO("mysql:dbname={$this->conf['dbname']};host={$slave['host']};port={$slave['port']}",
                    $this->conf['user'], $this->conf['passwd'],
                    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
            }
            $pdo = $this->pdoSlave;

        } else { // 其它查询使用主库
            if (!$this->pdoMaster) {
                $master = $this->conf['hosts']['master'];
                $this->pdoMaster = new PDO("mysql:dbname={$this->conf['dbname']};host={$master['host']};port={$master['port']}",
                    $this->conf['user'], $this->conf['passwd'],
                    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
            }
            $pdo = $this->pdoMaster;
        }

        $this->forceMaster(false); // 使用完后自动切换为非强制
        return call_user_func_array([$pdo, $name], $arguments);
    }

    /**
     * 下一次强制使用主库<br>
     * 操作完成后自动重置为非强制
     * @param bool $isForce
     * @return $this
     */
    public function forceMaster($isForce = true)
    {
        $this->isForceMaster = $isForce;
        return $this;
    }
}