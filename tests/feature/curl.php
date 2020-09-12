<?php

/**
 * \Core\AppCURL 测试
 */

require_once __DIR__ . '/../../init.php';

// 使用配置域名
$rs = curl_get(['rpc.sparrow', '/demo']);
var_dump($rs);

// 使用固定域名
$rs = curl_get('nginx:37064/demo');
var_dump($rs);

// post 测试
$rs = curl_post('nginx:37061/demo/xhr', ['first_name' => 'fist', 'last_name' => 'last']);
var_dump($rs);