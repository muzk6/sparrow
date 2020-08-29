<?php

/**
 * 前台入口
 */

use Core\Router;

/**
 * 前台主页
 */
route_get('/', function () {
    return 'Hello Sparrow.<br><a href="/demo">>>>查看示例</a>';
});

/**
 * Demo 示例
 */
route_group(function () {
    include PATH_ROUTES . '/demo.php';
});

route_middleware(function (Router $router) {
    logfile('access', [
        '__POST' => $_POST,
        'route' => $router->getMatchedRoute(),
    ], 'access');
});