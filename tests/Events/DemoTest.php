<?php

namespace Tests\Events;

use App\Events\DemoEvent;

class DemoTest extends \TestCase
{
    public function testDemoEvent()
    {
        $this->assertNull(app(DemoEvent::class)->handle());
    }

    public function testDemoEvent2()
    {
        $this->mockEvent(DemoEvent::class, 'test'); // 匹配后面 $this->mockEvent() 指定参数之外的任意参数
        $this->mockEvent(DemoEvent::class, 'test2', ['name' => 2]);
        $this->mockEvent(DemoEvent::class, 'test3', ['name' => 3]);

        $this->assertEquals('test', event(DemoEvent::class));
        $this->assertEquals('test2', event(DemoEvent::class, ['name' => 2]));
        $this->assertEquals('test3', event(DemoEvent::class, ['name' => 3]));
        $this->assertEquals('test', event(DemoEvent::class, ['any']));
    }
}
