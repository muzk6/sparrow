<?php

/**
 * OPS 运维与开发
 */

use Core\Router;

require dirname(__DIR__) . '/../init.php';

include PATH_ROUTES . '/OPS/index.php';

app(Router::class)->dispatch();