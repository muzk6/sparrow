<?php


namespace Core;


use Pimple\Container;
use Pimple\ServiceProviderInterface;
use ReflectionClass;

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
        $container = &self::$container;
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

    /**
     * 取容器对象、函数、数值
     * <p>支持递归取对象</p>
     * @param string $name
     * @return mixed
     */
    public static function get(string $name)
    {
        $container = self::init();

        // 存在容器中直接返回，或键名为非类名直接从容器里取值
        if (isset($container[$name]) || !class_exists($name)) {
            return $container[$name];
        }

        try {
            $ref = new ReflectionClass($name);
        } catch (\ReflectionException $e) {
            trigger_error($e->getMessage());
        }

        $constructor = $ref->getConstructor();
        if (!$constructor) {
            return $container[$name] = $ref->newInstance();
        }

        $params = $constructor->getParameters();
        $instanceArgs = [];
        foreach ($params as $param) {
            $instanceArgs[] = self::get($param->getClass()->getName());
        }

        return $container[$name] = $ref->newInstanceArgs($instanceArgs);
    }

}
