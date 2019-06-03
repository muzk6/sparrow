<?php

/**
 * yar 客户端
 */

require_once dirname(__DIR__) . '/init.php';

inject(function (\Core\Yar $appYar) {
    // 串行调用；
    // 开启 Xdebug Trace 跟踪: $appYar->trace('demo_bar')->bar('boo', 'bar')
    var_dump($appYar->client('demo')->bar('boo', 'bar'));

    echo str_repeat('-', 50) . PHP_EOL;

    $fn = function ($retval, $callinfo) {
        var_dump($retval, $callinfo); // $retval: 返回值; $callinfo: 请求信息(url, 目标函数名)
    };

    // 并行调用
    $appYar->concurrentClient('demo', $fn)->bar('a1', '并行调用1', 'callback');
    $appYar->concurrentClient('demo', $fn)->bar('a2', '并行调用2', 'callback');
    $appYar->concurrentLoop();
});
