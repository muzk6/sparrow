<?php

use Core\AppContainer;

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

if ($found) {
    $controllerNs = CONTROLLER_NAMESPACE . $controller;
    if (!is_callable([$controllerNs, $action])) {
        return app(\Core\AppResponseCode::class)->status404();
    }

    try {
        $ref = new ReflectionClass($controllerNs);
        $actionParams = [];
        foreach ($ref->getMethod($action)->getParameters() as $actionParam) {
            $actionParams[] = AppContainer::get($actionParam->getClass()->getName());
        }

        $controllerInstance = AppContainer::get($controllerNs);
        app(\Core\AppXdebug::class)->auto();

        // 执行控制方法
        echo call_user_func([$controllerInstance, $action], ...$actionParams);

    } catch (ReflectionException $e) {
        return app(\Core\AppResponseCode::class)->status404();
    }
} else {
    return app(\Core\AppResponseCode::class)->status404();
}
