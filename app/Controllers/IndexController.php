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
        echo '<script src="https://cdn.bootcss.com/jquery/3.3.1/jquery.min.js"></script>';
    }

    /**
     * @api
     * @post
     */
    public function api()
    {
        list($type, $err) = input('type', function ($val) {
            $val || panic(10001000);
        });

        if ($err) {
            panic($err['code']);
        }

        $data = DemoService::instance()->foo();
        if ($type == 1) {
            return $data;
        } else {
            return message([10002001, 'name' => 'Sparrow'], $data);
        }
    }
}
