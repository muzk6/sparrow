<?php

$dispatcher = FastRoute\simpleDispatcher(function (FastRoute\RouteCollector $r) {
    $r->addRoute('GET', '/', 'index');
    $r->addRoute(['GET', 'POST'], '/{ctl:[a-zA-Z]+}/{act:[a-zA-Z]+}', 'path');
});

// Fetch method and URI from somewhere
$httpMethod = $_SERVER['REQUEST_METHOD'];
$uri = $_SERVER['REQUEST_URI'];

// Strip query string (?foo=bar) and decode URI
if (false !== $pos = strpos($uri, '?')) {
    $uri = substr($uri, 0, $pos);
}
$uri = rawurldecode($uri);

$routeInfo = $dispatcher->dispatch($httpMethod, $uri);
switch ($routeInfo[0]) {
    case FastRoute\Dispatcher::NOT_FOUND:
        http_response_code(404);
        break;
    case FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
        $allowedMethods = $routeInfo[1];
        // ... 405 Method Not Allowed
        break;
    case FastRoute\Dispatcher::FOUND:
        $handler = $routeInfo[1];
        $vars = $routeInfo[2];
        if ($handler == 'index') {
            $controller = 'IndexController';
            $action = 'index';
        } else {
            $controller = "{$vars['ctl']}Controller";
            $action = $vars['act'];
        }

        $controllerNs = "App\\Controllers\\{$controller}";
        if (!is_callable([$controllerNs, $action])) {
            http_response_code(404);
            break;
        }

        call_user_func([new $controllerNs(), $action]);

        break;
}