<?php


namespace App\Controllers\OPS;


use Core\Auth;

class IndexController extends BaseOPSController
{
    /**
     * 登录登录
     */
    const LOGIN_PASSWD = 'ops.sparrow';

    /**
     * 主页
     * @return string
     */
    public function index()
    {
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
     * @throws \Core\AppException
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
