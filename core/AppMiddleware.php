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
     * 检查请求方法
     * @param Closure $next 下一个中间件
     * @param array $context 上下文参数
     */
    public function checkMethod(Closure $next, array $context)
    {
        if (isset($_SERVER['REQUEST_METHOD']) && strtolower($_SERVER['REQUEST_METHOD']) !== $context['middleware']) {
            http_response_code(405);
            return;
        }

        $next();
    }

    /**
     * 检查是否已登录
     * @param Closure $next 下一个中间件
     * @param array $context 上下文参数
     * @return bool
     */
    public function checkAuth(Closure $next, array $context)
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
    public function checkCSRF(Closure $next, array $context)
    {
        if (!csrf()->check()) {
            return;
        }

        $next();
    }

}