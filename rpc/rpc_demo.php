<?php

require_once dirname(__DIR__) . '/boot/init.php';

class Foo
{
    public function bar($parameter, $option = "foo")
    {
        return [$parameter, $option];
    }
}

$service = new Yar_Server(new Foo());
$service->handle();