<?php

/**
 * yar 服务端
 */

require_once dirname(__DIR__) . '/init.php';

class Foo
{
    public function bar($parameter, $option = 'foo')
    {
        return format2api([$parameter, $option]);
    }
}

app('core.yar')->server(new Foo());
