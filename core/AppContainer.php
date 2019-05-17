<?php


namespace Core;


use Pimple\Container;
use Pimple\ServiceProviderInterface;

/**
 * 容器
 * @package Core
 */
class AppContainer
{
    protected static $container = null;

    /**
     * 取容器对象
     * @return Container
     */
    public static function init()
    {
        $container = self::$container;
        if (!$container) {
            $container = new Container();
            $container->register(new CoreProvider());

            $appProviders = glob(PATH_APP . '/Providers/*.php');
            foreach ($appProviders as $appProvider) {
                $className = '\App\Providers\\' . rtrim(basename($appProvider), '.php');
                $obj = new $className();
                if (!($obj instanceof ServiceProviderInterface)) {
                    continue;
                }

                $container->register($obj);
            }
        }

        return $container;
    }
}
