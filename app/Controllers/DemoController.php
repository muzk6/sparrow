<?php


namespace App\Controllers;


use App\Services\DemoService;
use Core\AppException;
use Core\Auth;
use Core\BaseController;

/**
 * @package App\Controllers
 */
class DemoController extends BaseController
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
     * @return string
     * @throws AppException
     */
    public function index()
    {
        $this->input('get.title', 'Sparrow Demo');
        $inputs = $this->request();

        $this->assign('firstName', 'Hello');
        $this->assign('lastName', 'Sparrow');

        return $this->view('index', $inputs);
    }

    /**
     * 同步请求
     */
    public function doc()
    {
        try {
            csrf_check();

            $this->input('post.first_name')->required();
            $this->input('last_name')->required()->setTitle('名字');
            $inputs = $this->request(true); // 以并联方式验证

            flash_set('data', $inputs);
        } catch (AppException $appException) {
            flash_set('msg', $appException->getMessage());
            flash_set('data', $appException->getData());
        }

        back();
    }

    /**
     * 异步请求
     * @param DemoService $demo
     * @return array
     * @throws AppException
     */
    public function xhr(DemoService $demo)
    {
        csrf_check();

        $this->input('post.first_name')->required();
        $this->input('last_name')->required()->setTitle('名字');
        $inputs = $this->request(); // 以串联短路方式验证

        return [
            'inputs' => $inputs,
            'user_id' => $this->userId, // 获取登录后的 userId
            'foo' => $demo->foo(), // 通过自动依赖注入使用 DemoService 的对象
            'foo2' => app(DemoService::class)->foo(), // 或者通过容器使用 DemoService 的对象
        ];
    }

}
