<?php

namespace Core;

/**
 * 控制器中间件
 * @package Core
 */
class AppControllerMiddleware
{
    /**
     * 检查请求方法
     * @param string $refMethod
     * @return bool
     */
    public function checkMethod(string $refMethod)
    {
        if (strtolower(getenv('REQUEST_METHOD')) !== $refMethod) {
            http_response_code(405);
            return false;
        }

        return true;
    }

    /**
     * 检查是否已登录
     * @return bool
     */
    public function checkAuth()
    {
        if (!auth()->isLogin()) {
            http_response_code(401);
            return false;
        }

        return true;
    }

}