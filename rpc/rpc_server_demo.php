<?php

/**
 * yar 服务端
 */

use Core\Yar;

require_once dirname(__DIR__) . '/init.php';

class Foo extends \Core\BaseYar
{
    protected function bar($params)
    {
        return api_format(true, $params);
    }
}

/** @var Yar $yar */
$yar = app(Yar::class);
$yar->server(Foo::class);
