<?php


namespace App\Providers;


use App\Services\DemoService;
use Pimple\Container;
use Pimple\ServiceProviderInterface;

class ServiceProvider implements ServiceProviderInterface
{
    public function register(Container $pimple)
    {
        $pimple[DemoService::class] = function () {
        };
    }
}