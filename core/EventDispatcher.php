<?php


namespace Core;


use ReflectionClass;

class EventDispatcher
{
    /**
     * 触发事件
     * @param string $event 事件类名
     * @param array $params 事件参数
     * @return mixed
     */
    public function send(string $event, array $params = [])
    {
        try {
            $ref = new ReflectionClass($event);
            $handleParams = [];

            foreach ($ref->getMethod('handle')->getParameters() as $handleParam) {
                $dependClassName = $handleParam->getClass();
                if ($dependClassName) {
                    $handleParams[] = app($dependClassName->getName());
                } else {
                    $handleParams[] = $params;
                }
            }
            return call_user_func_array([app($event), 'handle'], $handleParams);
        } catch (\ReflectionException $e) {
            trigger_error($e->getMessage());
        }
    }

    /**
     * 触发异步事件
     * <p>
     * worker 位于 cli/worker.php<br>
     * PS. 异步worker 所有 include 的文件有变化时，会自动 exit. 因此 worker 必须使用 supervisor 管理
     * </p>
     * @param string $event 事件类名
     * @param array $params 事件参数
     * @return null
     */
    public function sendAsync(string $event, array $params = [])
    {
        /** @var Queue $queue */
        $queue = app(Queue::class);
        $queue->publish($event, $params);
        return null;
    }
}