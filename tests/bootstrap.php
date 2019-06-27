<?php

use Core\AppContainer;
use Core\EventDispatcher;
use PHPUnit\Framework\MockObject\MockObject;

require_once __DIR__ . '/../init.php';

defined('TEST_ENV') || define('TEST_ENV', true);

class TestCase extends \PHPUnit\Framework\TestCase
{
    /**
     * @var MockObject EventDispatcher的Mocker
     */
    private $eventMocker;

    /**
     * @var array mock事件集合
     */
    private $events;

    protected function setUp(): void
    {
        $this->eventMocker = null;
        $this->events = [];
    }

    /**
     * Mock事件
     * @param string $event 事件类名
     * @param mixed $willReturn 指定事件及其调用参数(如果有限定参数)下的返回值
     * @param array|null $withParams 指定限定参数，影响 $willReturn，不传时表示匹配任意调用参数
     * @return $this
     */
    public function mockEvent(string $event, $willReturn = null, array $withParams = [])
    {
        if (!$this->eventMocker) {
            $container = AppContainer::init();
            $this->eventMocker = $container[EventDispatcher::class] = $this->createMock(EventDispatcher::class);

            $this->eventMocker->method('sendAsync')->willReturn(null);
            $this->eventMocker->method('send')->willReturnCallback(function ($calledEvent, $calledParams) {
                foreach ($this->events as $mockEvent => $mockParams) {
                    if ($calledEvent === $mockEvent && !empty($calledParams)) {
                        foreach ($mockParams as $mockParam) {
                            if ($calledParams === $mockParam['params']) {
                                return $mockParam['willReturn'];
                            }
                        }
                    }
                }

                return $this->events[$calledEvent][md5(serialize([]))]['willReturn'] ?? null;
            });
        }

        $this->events[$event][md5(serialize($withParams))] = [
            'params' => $withParams,
            'willReturn' => $willReturn,
        ];

        return $this;
    }
}
