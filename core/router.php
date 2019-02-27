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
        http_response_code(404);
        return;
    }

    $next = function () use ($controllerNs, $action) {
        call_user_func([call_user_func([$controllerNs, 'instance']), $action]);
    };

    /** ================================= 中间件 ================================= */
    try {
        $reflector = new ReflectionClass($controllerNs);

        // 控制器 @mw
        $appDocReg = '#(?<=@mw\s).*#';
        $controllerDoc = $reflector->getDocComment();
        $appDocList = [];
        if (preg_match($appDocReg, $controllerDoc, $matchDoc)) {
            $appDocList = explode(',', trim($matchDoc[0]));
        }

        // 方法 @mw
        $actionDoc = $reflector->getMethod($action)->getDocComment();
        if (preg_match($appDocReg, $actionDoc, $matchDoc)) {
            $appDocList = array_merge($appDocList, explode(',', trim($matchDoc[0])));
        }

        if (!empty($appDocList)) {
            // 过滤前后空格、转小写
            foreach ($appDocList as &$appDocItem) {
                $appDocItem = strtolower(trim($appDocItem));
            }
            unset($appDocItem);

            if (!in_array('ignore', $appDocList)) {
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
                    $appDocItem = strtolower(trim($appDocItem));
                    $middlewareContext = [
                        'middleware' => $appDocItem,
                        'uri' => $uri,
                        'controller' => $controllerNs,
                        'action' => $action,
                    ];

                    switch ($appDocItem) {
                        case 'post': // 限于 POST 请求
                        case 'get': // 限于 GET 请求
                            $middlewareMethod = 'checkMethod';
                            break;
                        case 'auth': // 限于已登录
                            $middlewareMethod = 'checkAuth';
                            break;
                        case 'csrf': // csrf token 检验
                            $middlewareMethod = 'checkCSRF';
                            break;
                        default: // 自定义中间件
                            $middlewareMethod = $appDocItem;
                            break;
                    }

                    if (!method_exists($middlewareInstance, $middlewareMethod)) {
                        throw new Exception("中间件 Core\\AppMiddleware::{$middlewareMethod}() 不存在");
                    }

                    $next = function () use ($next, $middlewareInstance, $middlewareMethod, $middlewareContext) {
                        call_user_func_array([$middlewareInstance, $middlewareMethod], [$next, $middlewareContext]);
                    };
                }
            }
        }
    } catch (ReflectionException $e) {
        http_response_code(404);
        return;
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
    http_response_code(404);
}