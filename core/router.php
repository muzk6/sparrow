<?php

$uri = parse_url(rawurldecode($_SERVER['REQUEST_URI']), PHP_URL_PATH);

$found = false;
$controller = 'IndexController';
$action = 'index';
if ($uri === '/') {
    $found = true;
} elseif (preg_match('#(?<ctl>[a-zA-Z\d]+)/?(?<act>[a-zA-Z\d]+)?#', $uri, $matches)) {
    $found = true;

    $controller = ucfirst($matches['ctl']) . 'Controller';
    $action = $matches['act'] ?? 'index';
}

if ($found) {
    $controllerNs = "App\\Controllers\\{$controller}";
    if (!is_callable([$controllerNs, $action])) {
        http_response_code(404);
        return;
    }

    $instance = call_user_func([$controllerNs, 'instance']);

    // cli/trace.php 开启的 xdebug trace
    $traceStart = false;
    if (file_exists($traceConfFile = PATH_TRACE . '/config.php')) {
        $traceConf = include($traceConfFile);

        if ($traceConf['expire'] > time() // 检查过期
            && strpos(getenv('REQUEST_URI'), $traceConf['url']) !== false // 检查 url path 是否匹配
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
            str_replace('/', '_', getenv('REQUEST_URI'))
        );
        xdebug_start_trace(PATH_TRACE . '/' . $traceFilename);

        register_shutdown_function(function () {
            xdebug_stop_trace();
        });
    }

    call_user_func([$instance, $action]);
} else {
    http_response_code(404);
}