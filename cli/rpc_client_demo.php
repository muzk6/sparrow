<?php

/**
 * yar 客户端
 */

require_once dirname(__DIR__) . '/init.php';

// 串行调用；
// 开启 Xdebug Trace 跟踪: app('core.yar')->trace('demo_bar')->bar('boo', 'bar')
var_dump(app('core.yar')->client('demo')->bar('boo', 'bar'));

echo str_repeat('-', 50) . PHP_EOL;

// 并行调用
app('core.yar')->concurrentClient('demo', 'callback')->bar('a1', '并行调用1', 'callback');
app('core.yar')->concurrentClient('demo', 'callback')->bar('a2', '并行调用2', 'callback');
app('core.yar')->concurrentLoop();

function callback($retval, $callinfo)
{
    var_dump($retval, $callinfo); // $retval: 返回值; $callinfo: 请求信息(url, 目标函数名)
}
