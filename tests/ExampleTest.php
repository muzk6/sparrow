<?php

require_once __DIR__ . '/../init.php';

class ExampleTest extends \PHPUnit\Framework\TestCase
{
    public function testFoo()
    {
        $demo = new \App\Services\DemoService();
        $this->assertEquals('foo', $demo->foo());
    }
}

