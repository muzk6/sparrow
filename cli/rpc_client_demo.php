<?php

/**
 * yar 客户端
 */

use Core\Yar;

require dirname(__DIR__) . '/init.php';

// 串行调用；
// 开启 Xdebug Trace 跟踪: app(Yar::class)->trace('demo_bar');
print_r(app(Yar::class)->request('sparrow', 'bar', ['arg1', 'arg2']));

echo str_repeat('-', 50) . PHP_EOL;

$fn = function ($retval, $callinfo) {
    print_r([$retval, $callinfo]); // $retval: 返回值; $callinfo: 请求信息(url, 目标函数名)
};

// 并行调用
app(Yar::class)->requestConcurrently('sparrow', 'bar', ['name' => 'tom'], $fn);
app(Yar::class)->requestConcurrently('sparrow', 'bar', ['name' => 'tom'], $fn);
app(Yar::class)->loop();
