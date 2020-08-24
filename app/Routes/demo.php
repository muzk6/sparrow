<?php

/**
 * Demo 示例
 */

use App\Services\DemoService;
use Core\AppException;
use Core\Auth;

route_middleware(function (Auth $auth) {
    if (!IS_DEV) {
        http_response_code(404);
        exit;
    }

    if (!$auth->isLogin()) {
        //todo 未登录时的逻辑
    }
});

/**
 * 主页
 */
route_get_re('#^/demo(/index)?$#', function (Auth $auth) {
    $title = input('get.title', 'Sparrow Demo');

    assign('firstName', 'Hello'); // 定义模板变量
    assign('lastName', 'Sparrow');
    assign('userId', $auth->getUserId());

    return view('demo', ['title' => $title]); // 也可以在这里定义模板变量
});

/**
 * 同步请求
 */
route_post('/demo/doc', function () {
    try {
        csrf_check();

        // 部分验证，一个一个获取
        $firstName = input('post.first_name');
        $lastName = validate('last_name')->required()->get('名字');

        // 部分验证，全部获取
        $request = request();

        flash_set('data', ['first_name' => $firstName, 'last_name' => $lastName, 'request' => $request]);
    } catch (AppException $appException) {
        flash_set('msg', $appException->getMessage());
        flash_set('data', $appException->getData());
    }

    back();
});

/**
 * 异步请求
 */
route_post('/demo/xhr', function (DemoService $demoService, Auth $auth) {
    csrf_check();

    validate('post.first_name')->required();
    validate('last_name')->required()->setTitle('名字');
    $request = request(true); // 以并联方式验证

    return [
        'request' => $request,
        'user_id' => $auth->getUserId(), // 获取登录后的 userId
        'foo' => $demoService->foo(), // 通过自动依赖注入使用 DemoService 的对象
        'foo2' => app(DemoService::class)->foo(), // 或者通过容器使用 DemoService 的对象
    ];
});

// 登录
route_post('/demo/login', function () {
    try {
        csrf_check();

        $userId = validate('user_id:i')->gt(0)->get('用户ID ');

        app(Auth::class)->login($userId);
        flash_set('msg', '登录成功');

        redirect('/demo');
    } catch (AppException $appException) {
        flash_set('msg', $appException->getMessage());
        back();
    }
});

/**
 * 注销
 */
route_post('/demo/logout', function () {
    try {
        csrf_check();

        app(Auth::class)->logout();
        flash_set('msg', '注销成功');

        redirect('/demo');
    } catch (AppException $appException) {
        flash_set('msg', $appException->getMessage());
        back();
    }
});