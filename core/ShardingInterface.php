<?php

namespace Core;

/**
 * 数据库分区分表接口
 * @package Core
 */
interface ShardingInterface
{
    /**
     * 分区分表操作
     * @param int|string $shardKey 分区分表依据
     * @return static
     */
    public function sharding($shardKey);
}