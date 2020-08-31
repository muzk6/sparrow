<?php


namespace Core;

/**
 * Redis 缓存
 * @package Core
 */
class AppRedis extends \Redis
{
    /**
     * 集群实例化
     * @param array $conf
     * @return \Redis
     */
    public static function factory(array $conf)
    {
        shuffle($conf);

        $redis = new static();
        foreach ($conf as $host) {
            try {
                if ($redis->pconnect($host['host'], $host['port'], $host['timeout'])) {
                    $redis->setOption(AppRedis::OPT_PREFIX, $host['prefix']);
                    $redis->setOption(AppRedis::OPT_SERIALIZER, AppRedis::SERIALIZER_PHP);

                    break;
                }
            } catch (\Exception $exception) {
                trigger_error($exception->getMessage() . ': ' . json_encode($host, JSON_UNESCAPED_SLASHES), E_USER_WARNING);
            }
        }

        return $redis;
    }
}
