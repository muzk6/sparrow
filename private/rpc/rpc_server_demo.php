<?php

/**
 * yar 服务端
 */

use Core\BaseYar;
use Core\Yar;

require dirname(__DIR__) . '/../init.php';

app(Yar::class)->server(new class extends BaseYar
{
    public function bar()
    {
        return api_format(true, ['data' => func_get_args()]);
    }
});
