<?php

// 维护模式检查
if (is_file(dirname(__DIR__). '/data/down')) {
    http_response_code(403);
    exit;
}

require_once dirname(__DIR__) . '/boot/init.php';
require_once PATH_ROOT . '/boot/routes.php';