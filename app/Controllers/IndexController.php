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
        return success(10010, ['anmd' => 10]);
        $data = DemoService::instance()->foo();
        return $data;
    }
}
