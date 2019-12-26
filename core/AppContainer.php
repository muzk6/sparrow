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
            $container->register(new ServiceProvider());

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
     * 销毁容器
     */
    public static function destroy()
    {
        self::$container = null;
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
            $constructor = $ref->getConstructor();
            if (!$constructor) {
                $container[$name] = function () use ($ref) {
                    return $ref->newInstance();
                };
                return $container[$name];
            }

            $params = $constructor->getParameters();
            $instanceArgs = [];
            foreach ($params as $param) {
                $instanceArgs[] = self::get($param->getClass()->getName());
            }

            $container[$name] = function () use ($ref, $instanceArgs) {
                return $ref->newInstanceArgs($instanceArgs);
            };

            return $container[$name];
        } catch (\ReflectionException $e) {
            trigger_error($e->getMessage());
        }
    }

}
