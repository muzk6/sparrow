<?php


namespace Core;

use Redis;

/**
 * 节流阀
 * @package Core
 */
class Throttle
{
    /**
     * 频率限制
     * <p>ttl秒 内限制 limit次</p>
     * @param string $key 缓存key
     * @param int $limit 限制次数
     * @param int $ttl 指定秒数内
     * @return int 剩余次数，0表示这次是最后一次通过，下次就触发限制
     * @throws AppException ['reset' => 重置的时间点]
     */
    public function pass(string $key, int $limit, int $ttl)
    {
        /** @var Redis $redis */
        $redis = app(Redis::class);
        $now = time();
        if ($redis->lLen($key) < $limit) {
            $len = $redis->lPush($key, $now);
        } else {
            $earliest = intval($redis->lIndex($key, -1));
            if ($now - $earliest < $ttl) {
                $redis->expire($key, $ttl);
                panic(10001001, [
                    'reset' => $earliest + $ttl,
                ]);
            } else {
                $redis->lTrim($key, 1, 0);
                $len = $redis->lPush($key, $now);
            }
        }

        $redis->expire($key, $ttl);
        return $limit - $len;
    }
    
}
