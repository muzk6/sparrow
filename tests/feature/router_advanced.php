<?php

/**
 * 路由测试，中间件与路由
 * public/index.php 入口 include PATH_ROOT . '/tests/feature/router_advanced.php';
 * 测试路径：/, /index, /foo, /bar
 */

use Core\Router;

route_middleware(function () {
    echo '根组 前置中间件1<br>';
});

route_middleware(function () {
    echo '根组 前置中间件2<br>';
});

// 子组A
route_group(function () {
    route_middleware(function () {
        echo '子组A 前置中间件1<br>';
    });

    route_middleware(function () {
        echo '子组A 前置中间件2<br>';
    });

    // url: /, /index
    route_get_re('#^/(index)?$#', function (Router $router) {
        echo '主页<br>';
        echo '匹配路由：' . json_encode($router->getMatchedRoute(), JSON_UNESCAPED_SLASHES) . '<br>';
        echo '正则捕获：' . json_encode($router->getREMatches()) . '<br>';
    });

    route_middleware(function () {
        echo '子组A 后置中间件1<br>';
    });

    route_middleware(function () {
        echo '子组A 后置中间件2<br>';
    });
});

// 子组B
route_group(function () {
    route_middleware(function () {
        echo '子组B 前置中间件1<br>';
    });

    // url: /foo
    route_get('/foo', function (Router $router) {
        echo '匹配路由：' . json_encode($router->getMatchedRoute(), JSON_UNESCAPED_SLASHES) . '<br>';
        echo 'foo<br>';
    });

    route_middleware(function () {
        echo '子组B 后置中间件1<br>';
    });
});

// 子组C
route_group(function () {
    route_middleware(function () {
        echo '子组C 前置中间件1，抛出异常，或者 exit，跳过后面的所有前置中间件和路由回调，但不影响后置中间件<br>';
        panic('抛出 AppException');
    });

    route_middleware(function () {
        echo '子组C 前置中间件2<br>';
    });

    // url: /bar
    route_get('/bar', function () {
        echo 'bar<br>';
    });

    route_middleware(function () {
        echo '<br>子组C 后置中间件1<br>';
    });
});

route_middleware(function (Router $router) {
    echo '根组 后置中间件1<br>';
    echo '异常: ' . ($router->getAppException() ? '有' : '无') . '<br>';
});

route_middleware(function () {
    echo '根组 后置中间件2<br>';
});

app(Router::class)->setStatus404Handler(function () {
    echo '自定义404';
    http_response_code(404);
});

app(Router::class)->dispatch();