<?php

/**
 * php-cgi 测试入口
 * http://{HOST}/test.php
 */

require_once dirname(__DIR__) . '/init.php';

if (!app(\Core\Whitelist::class)->isSafeIp()) {
    http_response_code(404);
    exit;
}

inject(function () {
    try {
        //todo...
    } catch (\Core\AppException $appException) {
        var_dump(api_format($appException));
    }
});
