<?php

use Core\Router;

require dirname(__DIR__) . '/../init.php';

include PATH_ROUTES . '/RPC/index.php';

app(Router::class)->dispatch();