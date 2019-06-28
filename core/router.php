<?php

use Core\AppContainer;
use Core\Response;
use Core\Xdebug;
use Core\XHProf;

$uri = parse_url(rawurldecode($_SERVER['REQUEST_URI']), PHP_URL_PATH);

$found = false;
$controller = 'IndexController';
$action = 'index';
if ($uri === '/') {
    $found = true;
} elseif (preg_match('#^/(?<ctl>[a-zA-Z\d]+)/?(?<act>[a-zA-Z\d]+)?/?$#', $uri, $matches)) {
    $found = true;
    $controller = ucfirst($matches['ctl']) . 'Controller';
    $action = $matches['act'] ?? 'index';
}

/** @var Response $response */
$response = app(Response::class);

if ($found) {
    $controllerNs = CONTROLLER_NAMESPACE . $controller;
    if (!is_callable([$controllerNs, $action])) {
        return $response->status404();
    }

    try {
        $ref = new ReflectionClass($controllerNs);
        $actionParams = [];
        foreach ($ref->getMethod($action)->getParameters() as $actionParam) {
            $actionParams[] = AppContainer::get($actionParam->getClass()->getName());
        }

        $controllerInstance = AppContainer::get($controllerNs);

        /** @var XHProf $xhprof */
        $xhprof = app(XHProf::class);
        $xhprof->auto();

        /** @var Xdebug $xdebug */
        $xdebug = app(Xdebug::class);
        $xdebug->auto();

        // 执行控制方法
        echo call_user_func([$controllerInstance, $action], ...$actionParams);

    } catch (ReflectionException $e) {
        return $response->status404();
    }
} else {
    return $response->status404();
}
