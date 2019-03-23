<?php


namespace App\Controllers;


use App\Services\DemoService;
use Core\BaseController;

/**
 * @api
 * @package App\Controllers
 */
class IndexController extends BaseController
{
    /**
     * @get
     * @return array
     * @throws \Core\AppException
     */
    public function index()
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
