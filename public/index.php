<?php

use Core\Router;

// 维护模式检查
if (is_file(dirname(__DIR__) . '/data/down')) {
    http_response_code(503);
    echo '<p style="text-align:center;color:#B0BEC5;font-size:72px;font-weight:lighter;position:relative;top:40%;">Be right back.</p>';
    exit;
}

require dirname(__DIR__) . '/init.php';

include PATH_ROUTES . '/index.php';
app(Router::class)->dispatch();