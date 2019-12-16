<?php


namespace App\Controllers;


use App\Services\DemoService;
use Core\BaseController;

/**
 * @package App\Controllers
 */
class IndexController extends BaseController
{
    public function index()
    {
        begin();
        input('get.foo:i')->required();
        input('get.bar:i')->required();
        input('get.name')->required()->setTitle('名字');
        $inputs = validate();

        $row = app(DemoService::class)->foo();
        return ['inputs' => $inputs, 'row' => $row];
    }
}
