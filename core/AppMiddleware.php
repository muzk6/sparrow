<?php

namespace Core;

use Closure;

/**
 * 控制器中间件
 * <p>
 * $context = [ 'middleware' => ..., 'uri' => ..., 'controller' => ..., 'action' => ...]
 * </p>
 * @package Core
 */
class AppMiddleware
{
    /**
     * CSRF 校验
     * @param Closure $next 下一个中间件
     * @param array $context 上下文参数
     */
    public function csrf(Closure $next, array $context)
    {
        try {
            csrf()->check();
        } catch (AppException $e) {
            //todo 可在子类覆盖本方法修改默认行为
            echo $e->getMessage();
            return;
        }

        $next();

        // 请求完自动刷新令牌过期时间
        csrf()->refresh();
    }

    /**
     * POST 请求校验
     * @param Closure $next 下一个中间件
     * @param array $context 上下文参数
     */
    public function post(Closure $next, array $context)
    {
        if (!(IS_POST || IS_OPTIONS)) {
            http_response_code(405);
            return;
        }

        $next();
    }

    /**
     * GET 请求校验
     * @param Closure $next 下一个中间件
     * @param array $context 上下文参数
     */
    public function get(Closure $next, array $context)
    {
        if (!(IS_GET || IS_OPTIONS)) {
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
     * 请求频率限制
     * <p>默认 60秒 内限制 60次</p>
     * 带参数用法，60秒内限制10次: 10|throttle:60
     * @param Closure $next 下一个中间件
     * @param array $context 上下文参数
     */
    public function throttle(Closure $next, array $context)
    {
        $limit = intval($context['argv'][0]) ?? 60;
        $ttl = intval($context['argv'][1]) ?? 60;

        $key = 'THROTTLE:' . session_id() . ":{$context{'uri'}}";

        try {
            $remaining = throttle($key, $limit, $ttl);
            $reset = time() + $ttl;
        } catch (AppException $appException) {
            $remaining = 0;

            $exceptionData = $appException->getData();
            $reset = $exceptionData['reset'];
        }

        header("X-RateLimit-Limit: {$limit}");
        header("X-RateLimit-Remaining	: {$remaining}");
        header("X-RateLimit-Reset	: {$reset}");

        if (!$remaining) {
            http_response_code(429);
            return;
        }

        $next();
    }

}
