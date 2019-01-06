<?php


namespace App\Controllers;


class IndexController
{
    public function index()
    {
        $ds = app_db()->query('select * from test where id = 1')->fetch(2);
        echo app_view()->render('index.twig', ['name' => $ds['name']]);
    }
}