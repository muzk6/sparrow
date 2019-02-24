<?php

namespace Core;

/**
 * 控制器中间件
 * @package Core
 */
class AppMiddleware
{
    /**
     * 检查请求方法
     * @param array $context
     * @return bool
     */
    public function checkMethod(array $context)
    {
        if (isset($_SERVER['REQUEST_METHOD']) && strtolower($_SERVER['REQUEST_METHOD']) !== $context['middleware']) {
            http_response_code(405);
            return false;
        }

        return true;
    }

    /**
     * 检查是否已登录
     * @param array $context
     * @return bool
     */
    public function checkAuth(array $context)
    {
        if (!auth()->isLogin()) {
            http_response_code(401);
            return false;
        }

        return true;
    }

    /**
     * csrf token 校验
     * @param array $context
     * @return true
     * @throws AppException
     */
    public function checkCSRF(array $context)
    {
        return csrf()->check();
    }

}