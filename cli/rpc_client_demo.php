<?php

/**
 * yar 客户端
 */

require_once dirname(__DIR__) . '/init.php';

inject(function (\Core\Yar $yar) {
    // 串行调用；
    // 开启 Xdebug Trace 跟踪: $yar->start('demo_bar');
    var_dump($yar->request('demo', 'bar', ['name' => 'tom']));

    echo str_repeat('-', 50) . PHP_EOL;

    $fn = function ($retval, $callinfo) {
        var_dump($retval, $callinfo); // $retval: 返回值; $callinfo: 请求信息(url, 目标函数名)
    };

    // 并行调用
    $yar->requestConcurrently('demo', 'bar', ['name' => 'tom'], $fn);
    $yar->requestConcurrently('demo', 'bar', ['name' => 'tom'], $fn);
    $yar->loop();
});
