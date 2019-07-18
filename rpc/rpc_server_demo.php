<?php

/**
 * yar 服务端
 */

use Core\BaseYar;
use Core\Yar;

require_once dirname(__DIR__) . '/init.php';

class Foo extends BaseYar
{
    public function bar()
    {
        return api_format(true, ['data' => func_get_args()]);
    }
}

app(Yar::class)->server(Foo::class);
