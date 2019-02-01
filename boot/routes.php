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
            $controller = ucfirst($vars['ctl']) . 'Controller';
            $action = $vars['act'];
        }

        $controllerNs = "App\\Controllers\\{$controller}";
        if (!is_callable([$controllerNs, $action])) {
            http_response_code(404);
            break;
        }

        $instance = call_user_func([$controllerNs, 'instance']);

        // cli/trace.php 开启的 xdebug trace
        $traceStart = false;
        if (file_exists($traceConfFile = PATH_TRACE . '/config.php')) {
            $traceConf = include($traceConfFile);

            if ($traceConf['expire'] > time() // 检查过期
                && strpos(getenv('REQUEST_URI'), $traceConf['url']) !== false // 检查 url path 是否匹配
                && (!$traceConf['user_id'] || (auth()->isLogin() && $traceConf['user_id'] == auth()->userId())) // 检查特定用户
            ) {
                $traceStart = true;

                ini_set('xdebug.var_display_max_depth', $traceConf['max_depth']);
                ini_set('xdebug.var_display_max_data', $traceConf['max_data']);
                ini_set('xdebug.var_display_max_children', $traceConf['max_children']);
            }
        }

        // 对业务逻辑记录 xdebug trace
        if ($traceStart || isset($_REQUEST['xt']) || isset($_COOKIE['xt'])) {
            if (!file_exists(PATH_TRACE)) {
                mkdir(PATH_TRACE, 0777, true);
            }

            ini_set('xdebug.trace_format', 0);
            ini_set('xdebug.collect_return', 1);
            ini_set('xdebug.collect_params', 4);
            ini_set('xdebug.collect_assignments', 1);
            ini_set('xdebug.show_mem_delta', 1);
            ini_set('xdebug.collect_includes', 1);

            $traceFilename = sprintf('%s@%s@%s@%s',
                uniqid(), // 目的是排序用，和保证文件名唯一
                date('ymd_H:i:s'),
                auth()->userId(),
                str_replace('/', '_', getenv('REQUEST_URI'))
            );
            xdebug_start_trace(PATH_TRACE . '/' . $traceFilename);

            register_shutdown_function(function () {
                xdebug_stop_trace();
            });
        }

        call_user_func([$instance, $action]);

        break;
}