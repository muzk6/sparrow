<?php


namespace App\Controllers;


class IndexController
{
    public function index()
    {
        $ds = app_pdo()->query('select * from test where id = 1')->fetch(2);
        echo app_twig()->render('index.twig', ['name' => $ds['name']]);
    }
}