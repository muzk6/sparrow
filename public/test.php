<?php

/**
 * php-cgi 测试入口
 * http://{HOST}/test.php
 */

use Core\AppException;
use Core\Whitelist;

require_once dirname(__DIR__) . '/init.php';

/** @var Whitelist $whitelist */
$whitelist = app(Whitelist::class);
if (!$whitelist->isSafeIp()) {
    http_response_code(404);
    exit;
}

inject(function () {
    try {
        //todo...
    } catch (AppException $appException) {
        var_dump(api_format($appException));
    }
});
