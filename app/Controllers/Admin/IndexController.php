<?php


namespace App\Controllers\Admin;


use Core\BaseController;
use Core\Whitelist;

class IndexController extends BaseController
{
    public function beforeAction()
    {
        // 白名单以外直接 404
        if (!(app(Whitelist::class)->isSafeIp() || app(Whitelist::class)->isSafeCookie())) {
            http_response_code(404);
            exit;
        }
    }

    /**
     * 后台主页
     * @return string
     */
    public function index()
    {
        return 'Hello Admin.';
    }
}
