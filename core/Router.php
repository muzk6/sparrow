<?php


namespace Core;

/**
 * 路由注册器
 * @package Core
 */
class Router
{
    const TYPE_ROUTE = 1; // 路由
    const TYPE_MIDDLEWARE = 2; // 中间件

    /**
     * 路由
     * @var array
     */
    protected $routes = [];

    /**
     * 当前所属分组，支持嵌套
     * @var array
     */
    protected $groupStack = [];

    /**
     * 成功匹配的路由
     * @var array
     */
    protected $matchedRoute = [];

    /**
     * URL 正则捕获项
     * @var array
     */
    protected $reMatches = [];

    /**
     * 业务异常
     * @var AppException|null
     */
    protected $appException;

    /**
     * @var callable 响应 404 的回调函数
     */
    protected $status404Handler;

    /**
     * 添加路由
     * @param string $method
     * @param string $url
     * @param callable $action
     * @param array $opts
     */
    public function addRoute(string $method, $url, callable $action, array $opts = [])
    {
        $isRegexp = isset($opts['url_type']) && $opts['url_type'] == 'regexp';
        if (!$isRegexp && $url !== '/') {
            $url = rtrim($url, '/');
        }

        $method = strtoupper($method);
        $hash = md5("{$method}_{$url}_{$isRegexp}");
        static $duplicate = [];

        if (isset($duplicate[$hash])) {
            trigger_error('路由重复注册: ' . json_encode(['method' => $method, 'url' => $url, 'is_regexp' => $isRegexp], JSON_UNESCAPED_SLASHES), E_USER_WARNING);
            return;
        }

        $this->routes[] = [
            'type' => self::TYPE_ROUTE, // 路由
            'group' => $this->getLastGroup(), // 所属分组
            'method' => $method,
            'url' => $url,
            'is_regexp' => $isRegexp,
            'action' => $action,
        ];
        $duplicate[$hash] = 1;
    }

    /**
     * 路由中间件
     * @param callable $fn
     */
    public function addMiddleware(callable $fn)
    {
        $this->routes[] = [
            'type' => self::TYPE_MIDDLEWARE, // 中间件
            'group' => $this->getLastGroup(), // 所属分组
            'fn' => $fn,
        ];
    }

    /**
     * 路由分组，隔离中间件
     * @param callable $fn
     */
    public function addGroup(callable $fn)
    {
        $this->groupStack[] = uniqid();
        inject($fn);
        array_pop($this->groupStack);
    }

    /**
     * 当前分组与父分组
     * @return array [当前分组, 父分组]
     */
    protected function getLastGroup()
    {
        if (empty($this->groupStack)) {
            return ['', ''];
        }

        $count = count($this->groupStack);
        return [$this->groupStack[$count - 1], $this->groupStack[$count - 2] ?? ''];
    }

    /**
     * 路由分发
     */
    public function dispatch()
    {
        static $doing = false;
        if ($doing) { // 防止重复执行
            return;
        } else {
            $doing = true;
        }

        $requestUrl = parse_url(rawurldecode($_SERVER['REQUEST_URI']), PHP_URL_PATH);
        if ($requestUrl !== '/') {
            $requestUrl = rtrim($requestUrl, '/');
        }

        foreach ($this->routes as $routeIndex => $routeValue) {
            $found = false;
            $methodAllow = false;

            if ($routeValue['type'] !== self::TYPE_ROUTE) {
                continue;
            }

            if ($routeValue['is_regexp'] && preg_match($routeValue['url'], $requestUrl, $this->reMatches)) {
                $found = true;
            } elseif ($routeValue['url'] === $requestUrl) {
                $found = true;
            }

            if ($found) {
                $requestMethod = isset($_SERVER['REQUEST_METHOD']) ? strtoupper($_SERVER['REQUEST_METHOD']) : '';

                if ($requestMethod === $routeValue['method']) {
                    $methodAllow = true;
                } elseif ($routeValue['method'] === 'ANY') {
                    $methodAllow = true;
                } elseif ($requestMethod === 'OPTIONS') {
                    return;
                }

                if ($methodAllow) {
                    $this->matchedRoute = [
                        'method' => $routeValue['method'],
                        'url' => $routeValue['url'],
                        'is_regexp' => $routeValue['is_regexp'],
                    ];

                    // 路由后置勾子，register_shutdown_function 防止开发者业务逻辑里 exit
                    register_shutdown_function(function () use ($routeIndex, $routeValue) {
                        $this->runMiddleware(array_slice($this->routes, $routeIndex + 1), $routeValue['group'][0]);
                    });

                    app(XHProf::class)->auto();
                    app(Xdebug::class)->auto();

                    try {
                        // 路由前置勾子
                        $this->runMiddleware(array_slice($this->routes, 0, $routeIndex), $routeValue['group'][0]);

                        $out = inject($routeValue['action']);
                        if (is_array($out)) {
                            echo api_json(true, $out);
                        } else {
                            echo strval($out);
                        }
                    } catch (AppException $appException) {
                        echo api_json($this->appException = $appException);
                    }

                    return;
                }
            }
        }

        $this->fireStatus404();
    }

    /**
     * 运行中间件
     * @param array $routes
     * @param string $group 空时为根分组
     * @return void
     * @throws AppException
     */
    protected function runMiddleware(array $routes, string $group)
    {
        if (empty($routes)) {
            return;
        }

        $nextRoutes = [];
        $parentGroup = '';
        foreach ($routes as $v) {
            if ($v['type'] !== self::TYPE_MIDDLEWARE) {
                continue;
            }

            if ($v['group'][0] !== $group) {
                $nextRoutes[] = $v;
                continue;
            }

            $parentGroup = $v['group'][1];
            inject($v['fn']);
        }

        if ($parentGroup !== $group) {
            $this->runMiddleware($nextRoutes, $parentGroup);
        }
    }

    /**
     * 设置响应 404 的回调函数
     * @param callable $status404Handler
     * @return $this
     */
    public function setStatus404Handler(callable $status404Handler)
    {
        $this->status404Handler = $status404Handler;
        return $this;
    }

    /**
     * 触发 404 错误
     * @return mixed
     */
    public function fireStatus404()
    {
        http_response_code(404);

        if (is_callable($this->status404Handler)) {
            echo inject($this->status404Handler);
        }

        exit;
    }

    /**
     * 成功匹配的路由
     * @return array
     */
    public function getMatchedRoute()
    {
        return $this->matchedRoute;
    }

    /**
     * URL 正则捕获项
     * @return array
     */
    public function getREMatches()
    {
        return $this->reMatches;
    }

    /**
     * 业务异常
     * @return AppException|null
     */
    public function getAppException()
    {
        return $this->appException;
    }

}
