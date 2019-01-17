<?php


namespace App\Controllers;


use App\Services\DemoService;
use Core\BaseController;

class IndexController extends BaseController
{
    public function index()
    {
        $data = DemoService::instance()->foo();
        echo view()->render('index.twig', ['name' => $data['name']]);
    }
}