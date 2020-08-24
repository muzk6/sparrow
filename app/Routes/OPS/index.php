<?php

/**
 * OPS 主页
 */

use Core\Auth;
use Core\Whitelist;

const LOGIN_PASSWD = 'ops.sparrow';

route_middleware(function () {
    // 白名单以外直接 404
    if (!(app(Whitelist::class)->isSafeIp() || app(Whitelist::class)->isSafeCookie())) {
        http_response_code(404);
        exit;
    }
});

/**
 * 主页
 */
route_get('/', function (Auth $auth) {
    if (!$auth->isLogin()) {
        redirect('/index/login');
    }

    return view('ops/index');
});

/**
 * 登录页
 */
route_get('/index/login', function (Auth $auth) {
    if ($auth->isLogin()) {
        redirect('/');
    }

    return view('ops/login');
});

/**
 * 登录
 */
route_post('/index/login', function (Auth $auth) {
    $passwd = validate('post.passwd')->required()->get('密码');
    if ($passwd !== LOGIN_PASSWD) {
        return api_error('密码错误');
    }

    $auth->login('ops');

    return api_success();
});

/**
 * 退出登录
 */
route_any('/index/logout', function (Auth $auth) {
    $auth->logout();
    redirect('/index/login');
});

route_group(function () {
    route_middleware(function (Auth $auth) {
        if (!$auth->isLogin()) {
            redirect('/index/login');
        }
    });

    include PATH_ROUTES . '/OPS/log.php';
    include PATH_ROUTES . '/OPS/xdebug.php';
});