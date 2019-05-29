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
        return '<script src="https://cdn.bootcss.com/jquery/3.3.1/jquery.min.js"></script>';
    }

    public function api(DemoService $demoService)
    {
        list($req, $err) = input('type:i', 'require')->collect();

        try {
            if ($err) {
                panic(10001000, $err);
            }

            // 没有 return 时响应: {state: true, code: 0, msg: "", data: null}
            $data = $demoService->foo();
            if ($req['type'] == 1) {
                return $data;
            } elseif ($req['type'] == 2) {
                return json_api(true, message([10002001, 'name' => 'Sparrow'], ['foo' => $data]));
            }
        } catch (AppException $exception) {
            return json_api($exception);
        }
    }
}
