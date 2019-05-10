<?php


namespace App\Controllers;


use App\Services\DemoService;
use Core\BaseController;

/**
 * @package App\Controllers
 */
class IndexController extends BaseController
{
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
        $data = DemoService::instance()->foo();
        if ($req['type'] == 1) {
            return $data;
        } elseif ($req['type'] == 2) {
            return message([10002001, 'name' => 'Sparrow'], ['foo' => $data]);
        }
    }
}
