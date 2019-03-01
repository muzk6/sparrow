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
                    echo json_encode(response_format($responseContent));
                } else { // 网页响应格式
                    if ($responseContent instanceof Exception) {
                        throw $responseContent;
                    }

                    echo $responseContent;
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

    /** ================================= Xdebug Trace ================================= */
    // cli/trace.php 开启的 xdebug trace
    $traceStart = false;
    $traceConfFile = PATH_DATA . '/.tracerc';
    if (file_exists($traceConfFile)) {
        $traceConf = include($traceConfFile);

        if ($traceConf['expire'] > TIME // 检查过期
            && strpos($_SERVER['REQUEST_URI'], $traceConf['uri']) !== false // 检查 uri path 是否匹配
            && (!$traceConf['user_id'] || (auth()->isLogin() && $traceConf['user_id'] == auth()->userId())) // 有指定用户时，检查特定用户
        ) {
            $traceStart = true;

            ini_set('xdebug.var_display_max_depth', $traceConf['max_depth']);
            ini_set('xdebug.var_display_max_data', $traceConf['max_data']);
            ini_set('xdebug.var_display_max_children', $traceConf['max_children']);
        }
    }

    // 参数 xt=<value> 开启
    $xt = '';
    if (isset($_REQUEST['xt'])) {
        $xt = $_REQUEST['xt'];
    } elseif (isset($_COOKIE['xt'])) {
        $xt = $_COOKIE['xt'];
    }

    // 对业务逻辑记录 xdebug trace
    if ($traceStart || (whitelist()->isSafeIp() && $xt)) {
        if (!file_exists(PATH_TRACE)) {
            mkdir(PATH_TRACE, 0777, true);
        }

        ini_set('xdebug.trace_format', 0);
        ini_set('xdebug.collect_return', 1);
        ini_set('xdebug.collect_params', 4);
        ini_set('xdebug.collect_assignments', 1);
        ini_set('xdebug.show_mem_delta', 1);
        ini_set('xdebug.collect_includes', 1);

        $traceFilename = sprintf('%s.time:%s.xt:%s.uid:%s.uri:%s',
            uniqid(), // 目的是排序用，和保证文件名唯一
            date('ymd_His'),
            $xt,
            auth()->userId(),
            str_replace('/', '_', $_SERVER['REQUEST_URI'])
        );
        xdebug_start_trace(PATH_TRACE . '/' . $traceFilename);

        register_shutdown_function(function () {
            xdebug_stop_trace();
        });
    }
    /** ================================= /Xdebug Trace ================================= */

    $next();
} else {
    return core('AppResponseCode')->status404();
}