<?php


namespace App\Controllers;


class IndexController
{
    public function index()
    {
        $ds = db()->query('select * from test where id = 1')->fetch(2);
        echo view()->render('index.twig', ['name' => $ds['name']]);
    }
}