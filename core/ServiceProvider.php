<?php


namespace Core;


use Pimple\Container;
use Pimple\ServiceProviderInterface;

/**
 * 框架类容器提供器
 * @package App\Providers
 */
class ServiceProvider implements ServiceProviderInterface
{
    public function register(Container $pimple)
    {
        $pimple[PDOEngine::class] = function () {
            return new PDOEngine(config('database'));
        };

        $pimple[Aes::class] = function () {
            $conf = config('app');
            return new Aes($conf['secret_key']);
        };

        $pimple[Auth::class] = function () {
            $httpHost = isset($_SERVER['HTTP_HOST']) ? md5($_SERVER['HTTP_HOST']) : '';
            return new Auth(['prefix' => "AUTH:{$httpHost}:"]);
        };

        $pimple[Flash::class] = function () {
            $httpHost = isset($_SERVER['HTTP_HOST']) ? md5($_SERVER['HTTP_HOST']) : '';
            return new Flash(['prefix' => "FLASH:{$httpHost}:"]);
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

        $pimple[AppRedis::class] = $pimple['redis'] = function () {
            return AppRedis::factory(config('redis'));
        };

        $pimple[Blade::class] = function () {
            return new Blade(PATH_VIEW, PATH_DATA . '/view_cache');
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

    }

}
