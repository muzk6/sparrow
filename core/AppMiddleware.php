<?php

namespace Core;

use Closure;

/**
 * 控制器中间件
 * @package Core
 */
class AppMiddleware
{
    /**
     * post 校验
     * @param Closure $next 下一个中间件
     * @param array $context 上下文参数
     */
    public function post(Closure $next, array $context)
    {
        if (!IS_POST) {
            http_response_code(405);
            return;
        }

        $next();
    }

    /**
     * get 校验
     * @param Closure $next 下一个中间件
     * @param array $context 上下文参数
     */
    public function get(Closure $next, array $context)
    {
        if (!IS_GET) {
            http_response_code(405);
            return;
        }

        $next();
    }

    /**
     * 登录校验
     * @param Closure $next 下一个中间件
     * @param array $context 上下文参数
     */
    public function auth(Closure $next, array $context)
    {
        if (!auth()->isLogin()) {
            http_response_code(401);
            return;
        }

        $next();
    }

    /**
     * csrf token 校验
     * @param Closure $next 下一个中间件
     * @param array $context 上下文参数
     */
    public function csrf(Closure $next, array $context)
    {
        try {
            csrf()->check();
        } catch (AppException $e) {
            //todo 在子类覆盖本方法修改默认行为
            echo $e->getMessage();
            return;
        }

        $next();
    }

}