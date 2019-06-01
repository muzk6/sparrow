<?php

/**
 * php-cgi 测试入口
 * http://{HOST}/test.php
 */

require_once dirname(__DIR__) . '/init.php';

inject(function (\Core\AppWhitelist $appWhitelist) {
    if (!$appWhitelist->isSafeIp()) {
        http_response_code(404);
        exit;
    }

    try {
        //todo...
    } catch (\Core\AppException $appException) {
        var_dump(api_format($appException));
    }
});
