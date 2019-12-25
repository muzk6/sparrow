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
     * 连接资源引擎
     * @var PdoEngine|PDO
     */
    private $engine;

    public function __construct(PdoEngine $pdoEngine)
    {
        $this->engine = $pdoEngine;
    }

    /**
     * 所有PDO的底层查询都经过这里
     * @param string $name
     * @param array $arguments
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        return call_user_func_array([$this->engine, $name], $arguments);
    }

    public function selectOne(string $sql, array $binds = [], bool $useMaster = false)
    {
        $statement = $this->engine->prepare($sql);
        $statement->execute($binds);

        return $statement->fetch(PDO::FETCH_ASSOC);
    }

}
