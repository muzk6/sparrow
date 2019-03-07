<?php

/**
 * yar的服务端
 */

require_once dirname(__DIR__) . '/init.php';

class Foo
{
    public function bar($parameter, $option = 'foo')
    {
        return format2api([$parameter, $option]);
    }
}

$service = new Yar_Server(new Foo());

xdebug()->auto();
$service->handle();