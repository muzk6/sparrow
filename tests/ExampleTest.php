<?php

require_once __DIR__ . '/../init.php';

class ExampleTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockBuilder
     */
    protected $demoService;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $demoModel;

    protected function setUp()
    {
        $this->demoModel = $this->createMock(\App\Models\DemoModel::class);
        $this->demoService = $this->getMockBuilder(\App\Services\DemoService::class)
            ->setConstructorArgs([$this->demoModel]);
    }


    public function testFoo()
    {
        $this->demoModel->method('selectOne')->willReturn('foo');
        $mocker = $this->demoService->setMethodsExcept(['foo'])->getMock();

        $this->assertEquals('foo', $mocker->foo());
    }
}

