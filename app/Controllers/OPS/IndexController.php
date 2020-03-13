<?php


namespace App\Controllers\OPS;


use Core\AppException;
use Core\Auth;
use Core\Whitelist;

class IndexController extends BaseOPSController
{
    /**
     * 登录登录
     */
    const LOGIN_PASSWD = 'ops.sparrow';

    public function beforeAction()
    {
        // 白名单以外的 IP 直接 404
        app(Whitelist::class)->checkSafeIpOrExit();
    }

    /**
     * 主页
     * @return string
     */
    public function index()
    {
        if (!$this->isLogin) {
            redirect('/index/login');
            return false;
        }

        return view('ops/index');
    }

    public function login()
    {
        if ($this->isLogin) {
            return redirect('/');
        }

        return view('ops/login');
    }

    /**
     * 退出登录
     * @param Auth $auth
     */
    public function logout(Auth $auth)
    {
        $auth->logout();
        redirect('/index/login');
    }

    /**
     * 登录
     * @param Auth $auth
     * @return string
     * @throws AppException
     */
    public function postLogin(Auth $auth)
    {
        $passwd = validate('post.passwd')->required()->get('密码');
        if ($passwd !== self::LOGIN_PASSWD) {
            return api_error('密码错误');
        }

        $auth->login('ops');

        return api_success();
    }
}
