<?php

namespace Tests\Services;

use App\Services\DemoService;

class DemoTest extends \TestCase
{
    public function testFoo()
    {
        return $this->assertEquals('foo', app(DemoService::class)->foo());
    }
}