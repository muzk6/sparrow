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
     * @throws AppException
     */
    public function csrf(Closure $next, array $context)
    {
        app('app.csrf')->check();
        $next();
        app('app.csrf')->refresh();  // 请求完自动刷新令牌过期时间
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
     * @throws AppException
     */
    public function auth(Closure $next, array $context)
    {
        if (!app('app.auth')->isLogin()) {
            http_response_code(401);
            panic(10001005);
        }

        $next();
    }

    /**
     * 请求频率限制
     * <p>默认 60秒 内限制 60次</p>
     * 带参数用法，60秒内限制10次: 10|throttle:60
     * @param Closure $next 下一个中间件
     * @param array $context 上下文参数
     * @throws AppException
     */
    public function throttle(Closure $next, array $context)
    {
        $limit = isset($context['argv'][0]) ? intval($context['argv'][0]) : 60;
        $ttl = isset($context['argv'][1]) ? intval($context['argv'][1]) : 60;

        $key = 'THROTTLE:' . session_id() . ":{$context{'uri'}}";

        try {
            $stop = false;
            $remaining = throttle($key, $limit, $ttl);
            $reset = time() + $ttl;
        } catch (AppException $appException) {
            $stop = true;
            $remaining = 0;

            $exceptionData = $appException->getData();
            $reset = $exceptionData['reset'];
        }

        header("X-RateLimit-Limit: {$limit}");
        header("X-RateLimit-Remaining	: {$remaining}");
        header("X-RateLimit-Reset	: {$reset}");

        if ($stop) {
            http_response_code(429);
            panic([10001006, ['time' => date('H:i', $reset)]],
                ['limit' => $limit, 'ttl' => $ttl, 'reset' => $reset]);
        }

        $next();
    }

}
