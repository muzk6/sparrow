<?php

namespace Tests\Services;

use App\Services\DemoService;

class DemoTest extends \TestCase
{
    public function testFoo()
    {
        $mock = $this->createMock(DemoService::class);
        $mock->method('foo')->willReturn('bar');
        app_set(DemoService::class, $mock);

        return $this->assertEquals('bar', app(DemoService::class)->foo());
    }
}
