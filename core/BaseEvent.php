<?php


namespace Core;


use ReflectionClass;

/**
 * 事件基类
 * @package Core
 */
abstract class BaseEvent
{
    /**
     * 发送带上参数的当前事件
     * @param mixed ...$params
     * @return mixed
     */
    public function send(...$params)
    {
        try {
            $ref = new ReflectionClass(static::class);
            $listenerParams = [];
            foreach ($ref->getMethod('listen')->getParameters() as $handleParam) {
                $dependClassName = $handleParam->getClass();
                if ($dependClassName) {
                    $listenerParams[] = AppContainer::get($dependClassName->getName());
                } else {
                    $listenerParams[] = array_shift($params);
                }
            }
            return call_user_func([$this, 'listen'], ...$listenerParams);
        } catch (\ReflectionException $e) {
            trigger_error($e->getMessage());
        }
    }

    /**
     * 异步版 send()
     * @param mixed ...$params
     * @return null
     */
    public function sendAsync(...$params)
    {
        try {
            app(Queue::class)->publish(static::class, $params);
            return null;
        } catch (\ReflectionException $e) {
            trigger_error($e->getMessage());
        }
    }

}
