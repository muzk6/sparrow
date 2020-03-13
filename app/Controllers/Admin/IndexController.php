<?php


namespace App\Controllers\Admin;


use Core\BaseController;
use Core\Whitelist;

class IndexController extends BaseController
{
    public function beforeAction()
    {
        // 白名单以外的 IP 直接 404
        app(Whitelist::class)->checkSafeIpOrExit();
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
