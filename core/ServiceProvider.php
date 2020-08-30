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

        $pimple[Redis::class] = $pimple['redis'] = function () {
            if (!extension_loaded('redis')) {
                trigger_error('"pecl install redis" at first');
            }

            $conf = config('redis');
            shuffle($conf);

            $redis = new Redis();
            foreach ($conf as $host) {
                try {
                    if ($redis->pconnect($host['host'], $host['port'], $host['timeout'])) {
                        $redis->setOption(Redis::OPT_PREFIX, $host['prefix']);
                        $redis->setOption(Redis::OPT_SERIALIZER, Redis::SERIALIZER_PHP);

                        break;
                    }
                } catch (\Exception $exception) {
                    trigger_error($exception->getMessage() . ': ' . json_encode($host, JSON_UNESCAPED_SLASHES));
                }
            }

            return $redis;
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
