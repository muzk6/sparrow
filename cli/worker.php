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
});
