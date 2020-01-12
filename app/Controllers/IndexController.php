<?php


namespace App\Controllers;


use Core\BaseController;

/**
 * @package App\Controllers
 */
class IndexController extends BaseController
{
    /**
     * 前台主页
     * @return string
     */
    public function index()
    {
        return 'Hello Sparrow.<br><a href="/demo">>>>查看示例</a>';
    }
}
