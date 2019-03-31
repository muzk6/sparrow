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
    var_dump(db()->table('test')->orderBy(['expr' => '1 desc'])->selectAll(['expr' => '*'], null));
} catch (AppException $exception) {
    var_dump(format2api($exception));
}
