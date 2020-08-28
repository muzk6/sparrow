<?php

/**
 * 路由测试，实现 Controller::action 模式
 * public/index.php 入口 include PATH_ROOT . '/tests/feature/router_mvc.php';
 */

use Core\Router;

route_middleware(function () {
    echo '实现 Controller::action 模式<br>';
});

// url例子: /demo/foo
route_any_re('#^/(?<ct>[a-zA-Z_\d]+)/?(?<ac>[a-zA-Z_\d]+)?/?$#', function (Router $router) {
    $matches = $router->getREMatches();
    echo "Controller：{$matches['ct']}<br>";
    echo "Action：{$matches['ac']}<br>";
    echo '实例化看下面代码注释<br>';
    // $ctl = new $matches['ct']; // 实例化 Controller, 建议加上命名空间
    // return $ctl->$matches['ac'](); // 调用 action
});

app(Router::class)->dispatch();