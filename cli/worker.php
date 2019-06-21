<?php

/**
 * 异步事件 worker 入口
 * php worker.php -e "App\Events\DemoEvent"
 */

use Core\Queue;

require_once dirname(__DIR__) . '/init.php';

$opt = getopt('e:');
$event = $opt['e'] ?? null;

if (!$event) {
    echo '参考如下命令指定异步事件' . PHP_EOL;
    echo 'php worker.php -e "App\Events\DemoEvent"' . PHP_EOL;
    exit;
}

/** @var Queue $queue */
$queue = app(Queue::class);
$queue->consume($event, function ($params) use ($event) {
    $ref = new ReflectionClass($event);
    $listenerParams = [];

    foreach ($ref->getMethod('listen')->getParameters() as $handleParam) {
        $dependClassName = $handleParam->getClass();
        if ($dependClassName) {
            $listenerParams[] = app($dependClassName->getName());
        } else {
            $listenerParams[] = array_shift($params);
        }
    }
    return call_user_func([app($event), 'listen'], ...$listenerParams);
});
