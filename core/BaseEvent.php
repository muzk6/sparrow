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
     * 异步 send()
     * <p>
     * worker 位于 cli/worker.php<br>
     * PS. 异步worker 所有 include 的文件有变化时，会自动 exit. 因此 worker 必须使用 supervisor 管理
     * </p>
     * @param mixed ...$params
     * @return null
     */
    public function sendAsync(...$params)
    {
        /** @var Queue $queue */
        $queue = app(Queue::class);
        $queue->publish(static::class, $params);
        return null;
    }

}
