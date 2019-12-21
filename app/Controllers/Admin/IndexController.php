<?php


namespace App\Controllers\Admin;


use Core\BaseController;

class IndexController extends BaseController
{
    /**
     * 后台主页
     * @return string
     */
    public function index()
    {
        return 'Hello Admin.';
    }
}
