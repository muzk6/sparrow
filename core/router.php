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
        return app('app.response.code')->status404();
    }

    /** ================================= 中间件 ================================= */
    try {
        $reflector = new ReflectionClass($controllerNs);
        $controllerDoc = $reflector->getDocComment();
        $actionDoc = $reflector->getMethod($action)->getDocComment();

        $responseTypeApi = false;
        strpos($controllerDoc, '@api') !== false && $responseTypeApi = true;
        strpos($actionDoc, '@page') !== false && $responseTypeApi = false;
        strpos($actionDoc, '@api') !== false && $responseTypeApi = true;

        $response2client = function ($responseContent) use ($responseTypeApi) {
            if ($responseTypeApi) { // 接口响应格式
                headers_sent() || header('Content-Type: application/json; Charset=UTF-8');
                echo json_encode(format2api($responseContent));
            } else { // 网页响应格式
                if ($responseContent instanceof Exception) {
                    throw $responseContent;
                }

                echo (is_array($responseContent) || is_object($responseContent))
                    ? json_encode($responseContent)
                    : $responseContent;
            }
        };

        // 执行控制方法
        $next = function () use ($controllerNs, $action, $response2client) {
            $instance = AppContainer::get($controllerNs);

            $ref = new ReflectionClass($controllerNs);
            $actionParams = [];
            foreach ($ref->getMethod($action)->getParameters() as $actionParam) {
                $actionParams[] = AppContainer::get($actionParam->getClass()->getName());
            }

            $response2client(call_user_func([$instance, $action], ...$actionParams));
        };

        // 控制器中间件
        $appDocList = [];
        if (preg_match('#(?<=@middleware\s).*#', $controllerDoc, $matchDoc)) {
            $appDocList = explode(',', trim($matchDoc[0]));
        }

        // 方法中间件
        if (preg_match('#(?<=@)(?:post|get).*#', $actionDoc, $matchDoc)) {
            $appDocList = array_merge($appDocList, explode(',', trim($matchDoc[0])));
        }

        if (!empty($appDocList)) {
            $appDocItemPam = []; // 函数参数
            // 过滤前后空格，如果存在函数参数则提取出来
            foreach ($appDocList as $k => &$appDocItem) {
                $appDocItem = trim($appDocItem);
                if (strpos($appDocItem, '|') !== false) {
                    preg_match('#(?<=\|).*?(?:(?=\:)|(?=$))#', $appDocItem, $appDocItemMatch);
                    $appDocItemMethod = $appDocItemMatch[0];

                    $appDocItemPam[$appDocItemMethod][] = explode('|', $appDocItem)[0];

                    if (strpos($appDocItem, ':') !== false) {
                        $appDocItemPam[$appDocItemMethod] = array_merge(
                            $appDocItemPam[$appDocItemMethod],
                            array_slice(explode(':', $appDocItem), 1)
                        );
                    }

                    $appDocItem = $appDocItemMethod;
                }
            }
            unset($appDocItem);

            // 存在 ! 时去掉对应的中间件
            $appDocListFlip = array_flip($appDocList);
            foreach ($appDocList as $appDocItem) {
                if (strpos($appDocItem, '!') !== false) {
                    unset($appDocListFlip[$appDocItem]);
                    unset($appDocListFlip[substr($appDocItem, 1)]);
                }
            }

            $middlewareInstance = app('app.middleware');
            $appDocListRevert = array_reverse(array_flip($appDocListFlip));

            foreach ($appDocListRevert as $appDocItem) {
                $middlewareMethod = $appDocItem;
                $middlewareContext = [
                    'argv' => $appDocItemPam[$middlewareMethod] ?? [],
                    'middleware' => $middlewareMethod,
                    'uri' => $uri,
                    'controller' => $controllerNs,
                    'action' => $action,
                ];

                if (!method_exists($middlewareInstance, $middlewareMethod)) {
                    throw new Exception("中间件 Core\\AppMiddleware::{$middlewareMethod}() 不存在");
                }

                $next = function () use ($next, $response2client, $middlewareInstance, $middlewareMethod, $middlewareContext) {
                    try {
                        call_user_func_array([$middlewareInstance, $middlewareMethod], [$next, $middlewareContext]);
                    } catch (Exception $e) {
                        $response2client($e);
                    }
                };
            }
        }
    } catch (ReflectionException $e) {
        return app('app.response.code')->status404();
    }
    /** ================================= /中间件 ================================= */

    app('app.xdebug')->auto();
    $next();
} else {
    return app('app.response.code')->status404();
}
