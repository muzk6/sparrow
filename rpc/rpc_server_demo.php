<?php

/**
 * yar 服务端
 */

require_once dirname(__DIR__) . '/init.php';

class Foo
{
    public function bar($parameter, $option = 'foo')
    {
        return api_format([$parameter, $option], []);
    }
}

app(\Core\Yar::class)->server(new Foo());
