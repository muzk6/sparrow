<?php

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
        return core('AppResponseCode')->status404();
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

        // 执行控制方法
        $next = function () use ($controllerNs, $action, $responseTypeApi) {
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

            try {
                $responseContent = call_user_func([call_user_func([$controllerNs, 'instance']), $action]);
                $response2client($responseContent);
            } catch (Exception $e) {
                $response2client($e);
            }
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
            // 过滤前后空格、转小写
            foreach ($appDocList as &$appDocItem) {
                $appDocItem = strtolower(trim($appDocItem));
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

            /** @var \Core\AppMiddleware $middlewareInstance */
            $middlewareInstance = core('AppMiddleware');
            $appDocListRevert = array_reverse(array_flip($appDocListFlip));

            foreach ($appDocListRevert as $appDocItem) {
                $middlewareMethod = strtolower(trim($appDocItem));
                $middlewareContext = [
                    'middleware' => $middlewareMethod,
                    'uri' => $uri,
                    'controller' => $controllerNs,
                    'action' => $action,
                ];

                if (!method_exists($middlewareInstance, $middlewareMethod)) {
                    throw new Exception("中间件 Core\\AppMiddleware::{$middlewareMethod}() 不存在");
                }

                $next = function () use ($next, $middlewareInstance, $middlewareMethod, $middlewareContext) {
                    call_user_func_array([$middlewareInstance, $middlewareMethod], [$next, $middlewareContext]);
                };
            }
        }
    } catch (ReflectionException $e) {
        return core('AppResponseCode')->status404();
    }
    /** ================================= /中间件 ================================= */

    if (xdebug()->isOpen()) {
        xdebug()->trace();
    }

    $next();
} else {
    return core('AppResponseCode')->status404();
}