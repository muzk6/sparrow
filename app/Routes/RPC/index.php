<?php

use App\Services\DemoService;
use Core\Router;

route_get('/demo', function (DemoService $demoService) {
    return [
        'demo' => $demoService->foo(),
    ];
});

route_middleware(function (Router $router) {
    logfile('rpc', [
        '__POST' => $_POST,
        'route' => $router->getMatchedRoute(),
    ], 'rpc.access');
});