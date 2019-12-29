<?php


namespace App\Controllers;


use App\Services\DemoService;
use Core\AppException;
use Core\Auth;
use Core\BaseController;

/**
 * 示例控制器
 * @package App\Controllers
 */
class DemoController extends BaseController
{
    public function beforeAction()
    {
        if (!$this->isLogin) {
            //todo 未登录时的逻辑
        }

        if (isset($_GET['404'])) {
            return $this->httpResponse404(); // 响应 404, 并结束请求(因为这里返回了 false)
        }
    }

    /**
     * 主页
     * @return string
     */
    public function index()
    {
        $title = input('get.title', 'Sparrow Demo');

        assign('firstName', 'Hello'); // 定义模板变量
        assign('lastName', 'Sparrow');
        assign('userId', $this->userId);

        return view('demo', ['title' => $title]); // 也可以在这里定义模板变量
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

    /**
     * 登录
     */
    public function login()
    {
        try {
            csrf_check();

            $userId = validate('user_id:i')->gt(0)->setTitle('用户ID ')->get();

            app(Auth::class)->login($userId);
            flash_set('msg', '登录成功');

            return redirect('/demo');
        } catch (AppException $appException) {
            flash_set('msg', $appException->getMessage());
            return back();
        }
    }

    /**
     * 注销
     */
    public function logout()
    {
        try {
            csrf_check();

            app(Auth::class)->logout();
            flash_set('msg', '注销成功');

            return redirect('/demo');
        } catch (AppException $appException) {
            flash_set('msg', $appException->getMessage());
            return back();
        }
    }

}
