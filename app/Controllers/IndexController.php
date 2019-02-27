<?php


namespace App\Controllers;


use App\Services\DemoService;
use Core\BaseController;

class IndexController extends BaseController
{
    /**
     * @mw get
     */
    public function index()
    {
        $data = DemoService::instance()->foo();
        echo json_response($data);
    }
}