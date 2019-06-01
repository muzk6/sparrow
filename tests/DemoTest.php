<?php

require_once __DIR__ . '/../init.php';

class DemoTest extends \PHPUnit\Framework\TestCase
{
    public function testDemoEvent()
    {
        $mocker = $this->getMockBuilder(\App\Events\DemoEvent::class)
            ->setMethodsExcept(['listen'])
            ->getMock();
    }
}

