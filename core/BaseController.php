<?php

namespace Core;

use duncan3dc\Laravel\BladeInstance;

/**
 * 控制器基类
 * @package Core
 */
abstract class BaseController
{
    /**
     * 用户ID
     * @var int
     */
    protected $userId;

    /**
     * 是否已经登录
     * @var bool
     */
    protected $isLogin;

    private $assignVars = [];

    public function __construct(Auth $auth)
    {
        $this->userId = $auth->getUserId();
        $this->isLogin = $auth->isLogin();
    }

    /**
     * action 前置勾子
     */
    public function beforeAction()
    {
    }

    /**
     * action 后置勾子
     */
    public function afterAction()
    {
        logfile('access', ['__POST' => $_POST], 'access');
    }

    /**
     * 为视图模板分配变量
     * @param string $name
     * @param mixed $value
     */
    protected function assign(string $name, $value)
    {
        $this->assignVars[$name] = $value;
    }

    /**
     * 渲染视图模板
     * @param string $view 模板名
     * @param array $params 模板里的参数
     * @return string
     */
    protected function view(string $view, array $params = [])
    {
        $params = array_merge($this->assignVars, $params);
        $this->assignVars = [];

        return app(BladeInstance::class)->render($view, array_merge($this->assignVars, $params));
    }

}
