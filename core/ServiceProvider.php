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
        $pimple[PdoEngine::class] = function () {
            return new PdoEngine(config('database'));
        };

        $pimple[AppPDO::class] = $pimple->factory(function ($container) {
            return new AppPDO($container[PdoEngine::class]);
        });

        $pimple[Aes::class] = function () {
            $conf = config('app');
            return new Aes($conf['secret_key']);
        };

        $pimple[Auth::class] = function () {
            return new Auth(['prefix' => 'AUTH:']);
        };

        $pimple[Queue::class] = function () {
            return new Queue(config('rabbitmq'));
        };

        $pimple[Yar::class] = function () {
            return new Yar(config('yar'));
        };

        $pimple[Whitelist::class] = function () {
            return new Whitelist(config('whitelist'));
        };

        $pimple[Mail::class] = function () {
            return new Mail(config('email'));
        };

        $pimple[CSRF::class] = function () {
            $conf = config('app');
            $csrf = new CSRF([
                'secret_key' => $conf['secret_key'],
                'expire' => $conf['csrf_token_expire'],
            ]);

            return $csrf;
        };

        $pimple[Crypto::class] = function () {
            $conf = config('app');
            $csrf = new Crypto($conf['secret_key']);

            return $csrf;
        };

        $pimple[XHProf::class] = function () {
            return new XHProf(config('xhprof'));
        };

        $pimple[Redis::class] = function () {
            if (!extension_loaded('redis')) {
                trigger_error('"pecl install redis" at first');
            }

            $conf = config('redis');

            $redis = new Redis();
            $redis->pconnect($conf['host'], $conf['port'], $conf['timeout']);
            $redis->setOption(Redis::OPT_PREFIX, $conf['prefix']);
            $redis->setOption(Redis::OPT_SERIALIZER, Redis::SERIALIZER_PHP);

            return $redis;
        };

        /**
         * 文档 https://github.com/elastic/elasticsearch-php
         */
        $pimple[\Elasticsearch\Client::class] = function () {
            if (!class_exists('\Elasticsearch\ClientBuilder')) {
                trigger_error('"composer require elasticsearch/elasticsearch" at first');
            }

            $conf = config('elasticsearch');
            $hosts = $conf['hosts'];
            shuffle($hosts);

            $es = \Elasticsearch\ClientBuilder::create()
                ->setHosts($hosts)
                ->build();

            return $es;
        };

        $pimple[\duncan3dc\Laravel\BladeInstance::class] = function () {
            if (!class_exists('\duncan3dc\Laravel\BladeInstance')) {
                trigger_error('"composer require duncan3dc/blade" at first');
            }

            return new \duncan3dc\Laravel\BladeInstance(PATH_VIEW, PATH_DATA . '/view_cache');
        };

    }

}
