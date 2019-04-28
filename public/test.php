<?php

/**
 * php-cgi 测试入口
 * http://{HOST}/test.php
 */

use Core\AppException;

require_once dirname(__DIR__) . '/init.php';

if (!whitelist()->isSafeIp()) {
    http_response_code(404);
    exit;
}

try {
    //todo...
//    var_dump(input('get.b/基斯柯达基本'));
    var_dump(input('get.b:i/基斯柯达基本', ['lt:20'], 123, function ($val) {
    }));
} catch (AppException $exception) {
    var_dump(format2api($exception));
}
