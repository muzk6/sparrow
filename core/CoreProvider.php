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
        $pimple[AppPDO::class] = $pimple['app.db'] = function () {
            return new AppPDO(config('database'));
        };

        $pimple['app.aes'] = function () {
            $conf = config('app');
            return new AppAes($conf['secret_key']);
        };

        $pimple['app.auth'] = function () {
            return new AppAuth(['prefix' => 'AUTH:']);
        };

        $pimple['app.admin'] = function () {
            return new AppAuth(['prefix' => 'ADMIN:']);
        };

        $pimple['app.redis'] = function () {
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

        $pimple['app.queue'] = function () {
            if (!class_exists('\PhpAmqpLib\Connection\AMQPStreamConnection')) {
                throw new AppException('(composer require php-amqplib/php-amqplib) at first');
            }

            return new AppQueue(config('rabbitmq'));
        };

        $pimple['app.yar'] = function () {
            if (!class_exists('\Yar_Client')) {
                throw new AppException('(pecl install msgpack && pecl install yar) at first');
            }

            return new AppYar(config('yar'));
        };

        $pimple['app.whitelist'] = function () {
            return new AppWhitelist(config('whitelist'));
        };

        $pimple['app.mail'] = function () {
            if (!class_exists('\Swift_SmtpTransport')) {
                throw new AppException('(composer require swiftmailer/swiftmailer) at first');
            }

            return new AppMail(config('email'));
        };

        /**
         * 文档 https://github.com/elastic/elasticsearch-php
         */
        $pimple['app.es'] = function () {
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

        $pimple['app.flash'] = function () {
            return new AppFlash();
        };

        $pimple['app.xdebug'] = function () {
            return new AppXdebug();
        };

        $pimple['app.csrf'] = function () {
            $conf = config('app');
            $csrf = new AppCSRF([
                'secret_key' => $conf['secret_key'],
                'expire' => $conf['csrf_token_expire'],
            ]);

            return $csrf;
        };

        $pimple['app.response.code'] = function () {
            return new AppResponseCode();
        };

        $pimple['app.middleware'] = function () {
            return new \App\Core\AppMiddleware();
        };

    }

}
