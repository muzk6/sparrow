<?php


namespace App\Controllers;


use App\Services\DemoService;
use Core\AppException;
use Core\BaseController;

/**
 * @package App\Controllers
 */
class IndexController extends BaseController
{
    public function index()
    {
        try {
            $req = validate(function () {
                input('get.foo:i')->required();
                input('get.bar:i')->required();
                input('get.name')->required()->setTitle('名字');
            });

            $row = app(DemoService::class)->foo();
            return api_json(true, ['req' => $req, 'row' => $row]);
        } catch (AppException $exception) {
            return api_json($exception);
        }
    }
}
