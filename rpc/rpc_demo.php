<?php

require_once dirname(__DIR__) . '/boot/init.php';

//var_dump(extension_loaded('yar'));exit;
class Foo
{
    public function bar($parameter, $option = "foo")
    {
        return [$parameter, $option];
    }
}

$service = new Yar_Server(new Foo());
$service->handle();