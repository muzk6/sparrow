<?php


namespace App\Controllers\Admin;


use Core\BaseController;

class IndexController extends BaseController
{
    /**
     * @get
     */
    public function index()
    {
        return 'Welcome Admin!';
    }
}
