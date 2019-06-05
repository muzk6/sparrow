<?php

/**
 * yar 服务端
 */

require_once dirname(__DIR__) . '/init.php';

class Foo extends \Core\BaseYar
{
    protected function bar($params)
    {
        return api_format(true, $params);
    }
}

app(\Core\Yar::class)->server(Foo::class);
