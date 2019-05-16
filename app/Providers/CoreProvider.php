<?php


namespace App\Providers;


use Core\AppAes;
use Core\AppAuth;
use Pimple\Container;
use Pimple\ServiceProviderInterface;

/**
 * 框架类容器提供器
 * @package App\Providers
 */
class CoreProvider implements ServiceProviderInterface
{
    public function register(Container $pimple)
    {
        $pimple[AppAes::class] = function () {
            $conf = config('app');
            return core('AppAes', $conf['secret_key']);
        };

        $pimple[AppAuth::class] = function () {
            return core('AppAuth', 'AUTH:');
        };
    }

}