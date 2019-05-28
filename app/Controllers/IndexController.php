<?php


namespace App\Controllers;


use App\Models\DemoModel;
use App\Services\DemoService;
use Core\AppContainer;
use Core\BaseController;

/**
 * @package App\Controllers
 */
class IndexController extends BaseController
{
    public function __construct(DemoService $demoService, DemoModel $model)
    {
        AppContainer::init()['ab'] = AppContainer::init()->protect(function () {
            return mt_rand();
        });
        var_dump(app('ab')());
        AppContainer::init()['model'] = app(DemoModel::class);
    }

    public function test(DemoService $demoService, DemoModel $model)
    {
        var_dump(app('model'));
        var_dump($demoService->foo());
    }

    /**
     * @get
     */
    public function index()
    {
        return '<script src="https://cdn.bootcss.com/jquery/3.3.1/jquery.min.js"></script>';
    }

    /**
     * @api
     * @post
     * @throws \Core\AppException
     */
    public function api()
    {
        list($req, $err) = input('type:i', 'require')->collect();

        if ($err) {
            panic(10001000, $err);
        }

        // 没有 return 时响应: {state: true, code: 0, msg: "", data: null}
        $data = app('.DemoService')->foo();
        if ($req['type'] == 1) {
            return $data;
        } elseif ($req['type'] == 2) {
            return message([10002001, 'name' => 'Sparrow'], ['foo' => $data]);
        }
    }
}
