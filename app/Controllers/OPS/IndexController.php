<?php


namespace App\Controllers\OPS;


class IndexController extends BaseOPSController
{
    /**
     * 主页
     * @return string
     */
    public function index()
    {
        return view('ops/index');
    }
}
