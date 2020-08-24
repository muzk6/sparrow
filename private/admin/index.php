<?php

/**
 * 后台入口文件
 * 必须使用另一个域名指向此文件
 */

use Core\Router;

require dirname(__DIR__) . '/../init.php';

include PATH_ROUTES . '/admin.php';

app(Router::class)->dispatch();