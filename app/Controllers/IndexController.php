<?php


namespace App\Controllers;


use Core\BaseController;

/**
 * @package App\Controllers
 */
class IndexController extends BaseController
{
    /**
     * 主页
     * @return array
     * @throws \Core\AppException
     */
    public function index()
    {
        input('get.foo:i')->required();
        input('get.bar')->required()->setTitle('名字');
        $inputs = request();

        return ['inputs' => $inputs];
    }
}
