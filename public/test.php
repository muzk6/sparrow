<?php

/**
 * php-cgi 测试入口
 * http://{HOST}/test.php
 */

require_once dirname(__DIR__) . '/init.php';

if (!whitelist()->isSafeIp()) {
    http_response_code(404);
    exit;
}

//todo...
