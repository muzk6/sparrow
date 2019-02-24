<?php


namespace App\Controllers\Admin;


use Core\BaseController;

class IndexController extends BaseController
{
    /**
     * @mw get
     */
    public function index()
    {
        echo 'Welcome Admin!';
    }
}