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

    /**
     * @var callable 响应 404 的回调函数
     */
    protected $status404Handler;

    /**
     * BaseController constructor.
     * @param callable|null $status404Handler 响应 404 的回调函数
     */
    public function __construct(callable $status404Handler = null)
    {
        $this->status404Handler = $status404Handler;
        $this->userId = app(Auth::class)->getUserId();
        $this->isLogin = app(Auth::class)->isLogin();
    }

    /**
     * 响应 404
     * <p>里面的回调函数 $this->status404Handler 来自于 \Core\Router</p>
     * <p>在控制里需要主动调用的时候用到</p>
     * @return false
     */
    protected function httpResponse404()
    {
        if ($this->status404Handler) {
            call_user_func($this->status404Handler);
        }

        return false;
    }

    /**
     * action 前置勾子
     * @return mixed|false 当且仅当返回 false 时，将终止后面的 action 调用(afterAction 不受影响)
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

}
