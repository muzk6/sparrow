<?php


namespace App\Providers;


use App\Models\DemoModel;
use Pimple\Container;
use Pimple\ServiceProviderInterface;

/**
 * 模型类容器提供器
 * @package App\Providers
 */
class ModelProvider implements ServiceProviderInterface
{
    public function register(Container $pimple)
    {
        $pimple[DemoModel::class] = function () {
            return new DemoModel();
        };
    }

}
