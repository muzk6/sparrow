<?php


namespace App\Controllers;


use App\Services\DemoService;
use Core\BaseController;

class IndexController extends BaseController
{
    public function index()
    {
        ini_set('xdebug.trace_format', 0);
        ini_set('xdebug.collect_return', 1);
        ini_set('xdebug.collect_params', 4);
//        ini_set('xdebug.show_mem_delta', 1);
        system(':>' . PATH_LOG . '/xdebug_trace');
        xdebug_start_trace(PATH_LOG . '/xdebug_trace');
        $d = str_replace('s', 'r', 'src');
        $data = DemoService::instance()->foo();
        xdebug_stop_trace();
        echo view()->render('index', ['name' => $data['name']]);
    }
}