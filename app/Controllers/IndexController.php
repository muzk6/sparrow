<?php


namespace App\Controllers;


use App\Services\DemoService;
use Core\BaseController;

/**
 * @page
 * @middleware auth
 * @package App\Controllers
 */
class IndexController extends BaseController
{
    /**
     * @api
     * @get,!auth
     * @return array
     */
    public function index()
    {
        $data = DemoService::instance()->foo();
        return $data;
    }
}