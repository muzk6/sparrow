<?php

// 维护模式检查
if (is_file(dirname(__DIR__) . '/data/down')) {
    http_response_code(503);
    echo '<p style="text-align:center;color:#B0BEC5;font-size:72px;font-weight:lighter;position:relative;top:40%;">Be right back.</p>';
    exit;
}

require_once dirname(__DIR__) . '/boot/init.php';
require_once PATH_ROOT . '/boot/routes.php';