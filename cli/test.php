<?php

require_once dirname(__DIR__) . '/boot/init.php';

$client = new Yar_Client('http://localhost/knf/rpc/rpc_demo.php');
$rs = $client->bar($_SERVER, 'hello');
var_dump($rs);