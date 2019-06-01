<?php


namespace Core;


use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Redis;

/**
 * 框架类容器提供器
 * @package App\Providers
 */
class ServiceProvider implements ServiceProviderInterface
{
    public function register(Container $pimple)
    {
        $pimple[AppPdoEngine::class] = function () {
            return new AppPdoEngine(config('database'));
        };

        $pimple[AppPDO::class] = $pimple->factory(function ($container) {
            return new AppPDO($container[AppPdoEngine::class]);
        });

        $pimple[AppAes::class] = function () {
            $conf = config('app');
            return new AppAes($conf['secret_key']);
        };

        $pimple[AppAuth::class] = function () {
            return new AppAuth(['prefix' => 'AUTH:']);
        };
        
        $pimple[Redis::class] = function () {
            if (!extension_loaded('redis')) {
                throw new AppException('(pecl install redis) at first');
            }

            $conf = config('redis');

            $redis = new Redis();
            $redis->pconnect($conf['host'], $conf['port'], $conf['timeout']);
            $redis->setOption(Redis::OPT_PREFIX, $conf['prefix']);
            $redis->setOption(Redis::OPT_SERIALIZER, Redis::SERIALIZER_PHP);

            return $redis;
        };

        $pimple[AppQueue::class] = function () {
            if (!class_exists('\PhpAmqpLib\Connection\AMQPStreamConnection')) {
                throw new AppException('(composer require php-amqplib/php-amqplib) at first');
            }

            return new AppQueue(config('rabbitmq'));
        };

        $pimple[AppYar::class] = function () {
            if (!class_exists('\Yar_Client')) {
                throw new AppException('(pecl install msgpack && pecl install yar) at first');
            }

            return new AppYar(config('yar'));
        };

        $pimple[AppWhitelist::class] = function () {
            return new AppWhitelist(config('whitelist'));
        };

        $pimple[AppMail::class] = function () {
            if (!class_exists('\Swift_SmtpTransport')) {
                throw new AppException('(composer require swiftmailer/swiftmailer) at first');
            }

            return new AppMail(config('email'));
        };

        /**
         * 文档 https://github.com/elastic/elasticsearch-php
         */
        $pimple[\Elasticsearch\Client::class] = function () {
            if (!class_exists('\Elasticsearch\ClientBuilder')) {
                throw new AppException('(composer require elasticsearch/elasticsearch) at first');
            }

            $conf = config('elasticsearch');
            $hosts = $conf['hosts'];
            shuffle($hosts);

            $es = \Elasticsearch\ClientBuilder::create()
                ->setHosts($hosts)
                ->build();

            return $es;
        };

        $pimple[AppCSRF::class] = function () {
            $conf = config('app');
            $csrf = new AppCSRF([
                'secret_key' => $conf['secret_key'],
                'expire' => $conf['csrf_token_expire'],
            ]);

            return $csrf;
        };

        $pimple[AppResponseCode::class] = function () {
            return new AppResponseCode();
        };

    }

}
