<?php

/**
 * Yar 客户端
 */

use Core\Yar;

require_once __DIR__ . '/../../init.php';

/**
 * 串行调用
 */

// 使用配置好的 URL, 配置位于 config/dev/yar.php
var_dump(app(Yar::class)->request('sparrow', 'foo', ['arg1', 'arg2']));

// 直接使用 URL, 多用于临时场景
var_dump(app(Yar::class)->request('http://nginx:37064/demo', 'foo', ['arg1', 'arg2']));

// 使用 Xdebug Trace 跟踪
app(Yar::class)->trace('yar_trace')->request('sparrow', 'foo', ['arg1', 'arg2']);


echo str_repeat('-', 50) . PHP_EOL;


$fn = function ($ret, $info) {
    var_dump([$ret, $info]); // $ret: 返回值; $info: 请求信息(url, 目标函数名)
};

/**
 * 并行调用
 */

// 只注册正常回调，遇到错误时由 \Yar_Concurrent_Client::loop() 抛出异常，不会中断代码
app(Yar::class)->requestConcurrently('sparrow', 'foo', ['name' => 'tom1'],
    function ($retVal, $callInfo) {
        echo '正常回调1' . PHP_EOL;
        var_dump($retVal, $callInfo);
    }
);

// 同时注册正常、错误回调
app(Yar::class)->requestConcurrently('sparrow', 'foo', ['name' => 'tom2'],
    function ($retVal, $callInfo) {
        echo '正常回调2' . PHP_EOL;
        var_dump($retVal, $callInfo);
    },
    function ($type, $error, $callInfo) {
        echo '错误回调2' . PHP_EOL;
        var_dump($type, $error, $callInfo);
    }
);

// 开始执行
app(Yar::class)->loop();