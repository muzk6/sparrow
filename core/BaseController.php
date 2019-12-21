<?php

namespace Core;

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
        logfile('access', ['__POST' => $_POST], '__access');
    }

}
