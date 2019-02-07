<?php

namespace Core;

/**
 * 数据库分表接口
 * @package Core
 */
interface ShardingInterface
{
    /**
     * 分表操作
     * @param int|string $shardKey 分表依据
     * @return static
     */
    public function sharding($shardKey);
}