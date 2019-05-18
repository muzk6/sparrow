<?php

/**
 * yar 客户端
 */

require_once dirname(__DIR__) . '/init.php';

// 串行调用；
// 开启 Xdebug Trace 跟踪: app('app.yar')->trace('demo_bar')->bar('boo', 'bar')
var_dump(app('app.yar')->client('demo')->bar('boo', 'bar'));

echo str_repeat('-', 50) . PHP_EOL;

// 并行调用
app('app.yar')->concurrentClient('demo', 'callback')->bar('a1', '并行调用1', 'callback');
app('app.yar')->concurrentClient('demo', 'callback')->bar('a2', '并行调用2', 'callback');
app('app.yar')->concurrentLoop();

function callback($retval, $callinfo)
{
    var_dump($retval, $callinfo); // $retval: 返回值; $callinfo: 请求信息(url, 目标函数名)
}
