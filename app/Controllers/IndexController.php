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
     */
    public function index()
    {
        $data = DemoService::instance()->foo();
        return $data;
    }
}
