<?php


namespace App\Controllers;


use App\Services\DemoService;
use Core\AppException;
use Core\BaseController;

class IndexController extends BaseController
{
    /**
     * @mw get
     */
    public function index()
    {
        var_dump(input('get.a', function ($val) {
            if (is_null($val)) {
                throw new \Exception('not set');
            }
        }));
        var_dump(input('a', 'abc'));
        var_dump(input('a'));
        var_dump(input('get.', function ($val, $name) {
            if (empty($val)) {
                throw new AppException("{$name} required!");
            }
            return "{$name}_{$val}";
        }));
        input([
            'get',
            'a' => 'abc',
            'b' => true,
            'c' => function ($val) {
                return 'callback: ' . $val;
            }
        ]);

        exit;
        $data = DemoService::instance()->foo();
        echo json_response($data['name']);
    }
}