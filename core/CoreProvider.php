<?php


namespace Core;


use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Redis;

/**
 * 框架类容器提供器
 * @package App\Providers
 */
class CoreProvider implements ServiceProviderInterface
{
    public function register(Container $pimple)
    {
        $pimple['core.aes'] = function () {
            $conf = config('app');
            return new AppAes($conf['secret_key']);
        };

        $pimple['core.auth'] = function () {
            return new AppAuth(['prefix' => 'AUTH:']);
        };

        $pimple['core.redis'] = function () {
            if (!extension_loaded('redis')) {
                throw new AppException('pecl install redis');
            }

            $conf = config('redis');

            $redis = new Redis();
            $redis->pconnect($conf['host'], $conf['port'], $conf['timeout']);
            $redis->setOption(Redis::OPT_PREFIX, $conf['prefix']);
            $redis->setOption(Redis::OPT_SERIALIZER, Redis::SERIALIZER_PHP);

            return $redis;
        };

        $pimple['core.queue'] = function () {
            if (!class_exists('\PhpAmqpLib\Connection\AMQPStreamConnection')) {
                throw new AppException('composer require php-amqplib/php-amqplib');
            }

            return new AppQueue(config('rabbitmq'));
        };

        $pimple['core.yar'] = function () {
            if (!class_exists('\Yar_Client')) {
                throw new AppException('pecl install msgpack && pecl install yar');
            }

            return new AppYar(config('yar'));
        };



    }

}
