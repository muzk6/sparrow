<?php

/**
 * yar 服务端
 */

require_once dirname(__DIR__) . '/init.php';

class Foo extends \Core\BaseYar
{
    protected function bar($params, \App\Events\DemoEvent $demoEvent)
    {
        $ds = $demoEvent->send($params['name']);
        return api_format(true, $ds);
    }
}

app(\Core\Yar::class)->server(Foo::class);
