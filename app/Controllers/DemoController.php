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
     */
    public function index()
    {
        $title = input('get.title', 'Sparrow Demo');

        $this->assign('firstName', 'Hello');
        $this->assign('lastName', 'Sparrow');
        return $this->view('index', ['title' => $title]);
    }

    /**
     * 同步请求
     */
    public function doc()
    {
        try {
            csrf_check();

            // 部分验证，一个一个获取
            $firstName = input('post.first_name');
            $lastName = validate('last_name')->required()->setTitle('名字')->get();

            // 部分验证，全部获取
            $request = request();

            flash_set('data', ['first_name' => $firstName, 'last_name' => $lastName, 'request' => $request]);
        } catch (AppException $appException) {
            flash_set('msg', $appException->getMessage());
            flash_set('data', $appException->getData());
        }

        return back();
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

        validate('post.first_name')->required();
        validate('last_name')->required()->setTitle('名字');
        $request = request(true); // 以并联方式验证

        return [
            'request' => $request,
            'user_id' => $this->userId, // 获取登录后的 userId
            'foo' => $demo->foo(), // 通过自动依赖注入使用 DemoService 的对象
            'foo2' => app(DemoService::class)->foo(), // 或者通过容器使用 DemoService 的对象
        ];
    }

}
