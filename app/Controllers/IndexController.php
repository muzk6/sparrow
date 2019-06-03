<?php


namespace App\Controllers;


use App\Events\DemoEvent;
use Core\AppException;
use Core\BaseController;

/**
 * @package App\Controllers
 */
class IndexController extends BaseController
{
    public function index(DemoEvent $demoEvent)
    {
        try {
            $req = validate(function () {
                input('get.foo:i')->required();
                input('get.bar:i')->required();
                input('get.name')->required()->setTitle('名字');
            });

            $row = $demoEvent->send($req['name']);

            return api_json(true, ['req' => $req, 'row' => $row]);
        } catch (AppException $exception) {
            return api_json($exception);
        }
    }
}
