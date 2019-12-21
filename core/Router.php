<?php

namespace Core;

use ReflectionClass;
use ReflectionException;

/**
 * 路由
 * @package Core
 */
class Router
{
    /**
     * @var array 配置文件
     */
    protected $conf = [];

    /**
     * @var string 获取请求的 url
     */
    protected $url = '';

    /**
     * @var array 正则匹配的分组
     */
    protected $matchGroups = [];

    /**
     * @var array 匹配到的路由规则
     */
    protected $matchRule = [];

    /**
     * @var callable 响应 404 的回调函数
     */
    protected $status404Handler;

    public function __construct(array $conf)
    {
        $this->conf = $conf;

        $this->setStatus404Handler(function () {
            http_response_code(404);
        });
    }

    /**
     * 路由分发
     * @param string $group 路由组
     * @return void
     */
    public function dispatch(string $group = 'default')
    {
        $this->url = parse_url(rawurldecode($_SERVER['REQUEST_URI']), PHP_URL_PATH);
        $this->matchRule['group'] = $group;
        $found = false;
        $routeGroup = $this->conf[$group] ?? [];
        $controller = '';
        $action = '';

        foreach ($routeGroup as $seq => $rule) {
            if (preg_match($rule['pattern'], $this->url, $this->matchGroups)) {
                if (isset($rule['controller'])) {
                    $controller = $rule['controller'];
                    $action = $this->matchGroups['ac'] ?? 'index';

                } elseif (isset($rule['action'])) {
                    [$controller, $action] = explode('@', $rule['action']);

                } elseif (isset($rule['namespace'])) {
                    $controller = $rule['namespace']
                        . ucfirst($this->matchGroups['ct'] ?? 'index')
                        . 'Controller';
                    $action = $this->matchGroups['ac'] ?? 'index';

                } else {
                    trigger_error('config/routes.php not setting `controller` or `action` or `namespace`');
                    exit;
                }

                $found = true;
                $this->matchRule['seq'] = $seq;
                $this->matchRule['rule'] = $rule;
                break;
            }
        }

        if ($found) {
            if (!is_callable([$controller, $action])) {
                return call_user_func($this->status404Handler);
            }

            try {
                $ref = new ReflectionClass($controller);
                $actionParams = [];
                foreach ($ref->getMethod($action)->getParameters() as $actionParam) {
                    $actionParams[] = AppContainer::get($actionParam->getClass()->getName());
                }

                $controllerInstance = AppContainer::get($controller);

                /** @var XHProf $xhprof */
                $xhprof = app(XHProf::class);
                $xhprof->auto();

                /** @var Xdebug $xdebug */
                $xdebug = app(Xdebug::class);
                $xdebug->auto();

                try {
                    // 执行 action 前置勾子
                    is_callable([$controllerInstance, 'beforeAction']) && call_user_func([$controllerInstance, 'beforeAction']);

                    // 执行控制方法 action
                    $out = call_user_func([$controllerInstance, $action], ...$actionParams);
                    if (is_array($out)) {
                        echo api_json(true, $out);
                    } else {
                        echo strval($out);
                    }
                } catch (AppException $appException) {
                    echo api_json($appException);
                } finally {
                    // 执行 action 后置勾子
                    is_callable([$controllerInstance, 'afterAction']) && call_user_func([$controllerInstance, 'afterAction']);
                }

            } catch (ReflectionException $e) {
                return call_user_func($this->status404Handler);
            }
        } else {
            return call_user_func($this->status404Handler);
        }
    }

    /**
     * 获取请求的 url
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * 获取正则匹配的分组
     * @return array
     */
    public function getMatchGroups()
    {
        return $this->matchGroups;
    }

    /**
     * 获取匹配到的路由规则
     * @return array
     */
    public function getMatchRule()
    {
        return $this->matchRule;
    }

    /**
     * 设置响应 404 的回调函数
     * @param callable $status404Handler
     * @return Router
     */
    public function setStatus404Handler($status404Handler)
    {
        $this->status404Handler = $status404Handler;
        return $this;
    }

}
