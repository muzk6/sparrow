<?php

/**
 * 业务后台
 */

use Core\Whitelist;


route_middleware(function () {
    // 白名单以外直接 404
    if (!(app(Whitelist::class)->isSafeIp() || app(Whitelist::class)->isSafeCookie())) {
        http_response_code(404);
        exit;
    }
});

route_get('/', function () {
    return 'Hello Admin.';
});