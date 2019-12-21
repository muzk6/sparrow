<?php


namespace App\Controllers;


use App\Services\DemoService;
use Core\AppException;
use Core\Auth;
use Core\BaseController;

/**
 * @package App\Controllers
 */
class IndexController extends BaseController
{
    public function __construct(Auth $auth)
    {
        parent::__construct($auth);

        if (!$this->isLogin) {
            //todo 未登录时的逻辑
        }
    }

    /**
     * 主页
     * @param DemoService $demo
     * @return array
     * @throws AppException
     */
    public function index(DemoService $demo)
    {
        input('get.foo:i')->required();
        input('get.bar')->required()->setTitle('名字');
        $inputs = request();

        return [
            'inputs' => $inputs,
            'user_id' => $this->userId, // 取登录后的 userId
            'foo' => $demo->foo(), // 通过自动依赖注入使用 DemoService 的对象
            'foo2' => app(DemoService::class)->foo(), // 或者通过容器使用 DemoService 的对象
        ];
    }
}
