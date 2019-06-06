<?php

namespace Tests\Events;

class DemoTest extends \TestCase
{
    public function testDemoEvent()
    {
        $mocker = $this->getMockBuilder(\App\Events\DemoEvent::class)
            ->setMethodsExcept(['listen'])
            ->getMock();

        $this->assertNull($mocker->listen());
    }
}

