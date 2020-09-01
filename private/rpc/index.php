<?php

/**
 * Yar 服务端
 */

use App\Services\DemoService;
use Core\Router;
use Core\Yar;

require dirname(__DIR__) . '/../init.php';

route_any('/demo', function (Yar $yar, DemoService $demoService) {
    $yar->server($demoService);
});

app(Router::class)->dispatch();