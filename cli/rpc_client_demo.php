<?php

/**
 * yar的客户端
 */

require_once dirname(__DIR__) . '/boot/init.php';

$conf = config('yar');
$url = $conf['knf'] . '/rpc_server_demo.php';

$client = new Yar_Client($url);
$rs = $client->bar(['foo', 'bar'], '串行调用');
var_dump($rs);

echo str_repeat('-', 50) . PHP_EOL;

Yar_Concurrent_Client::call($url, 'bar', ['a1', '并行调用1'], 'callback');
Yar_Concurrent_Client::call($url, 'bar', ['a2', '并行调用2'], 'callback');
Yar_Concurrent_Client::loop();

function callback($retval, $callinfo)
{
    var_dump($retval, $callinfo); // 返回值, 请求信息(url, 目标函数名)
}