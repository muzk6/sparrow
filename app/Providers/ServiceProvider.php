<?php


namespace App\Providers;


use App\Services\DemoService;
use Pimple\Container;
use Pimple\ServiceProviderInterface;

/**
 * 服务类容器提供器
 * @package App\Providers
 */
class ServiceProvider implements ServiceProviderInterface
{
    public function register(Container $pimple)
    {
        $pimple['.DemoService'] = function () {
            return new DemoService();
        };
    }
}
