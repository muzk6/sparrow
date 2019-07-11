<?php


namespace App\Providers;


use Pimple\Container;
use Pimple\ServiceProviderInterface;

/**
 * 示例容器服务提供器
 * @package App\Providers
 */
class DemoProvider implements ServiceProviderInterface
{
    public function register(Container $pimple)
    {
        // 参考 \Core\ServiceProvider
    }
}
