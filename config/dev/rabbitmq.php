<?php

/**
 * 队列
 */

return [
    'host' => 'localhost',
    'port' => 5672,
    'user' => 'guest',
    'passwd' => 'guest',
    'exchange_name' => 'sparrow.direct',
    'exchange_type' => 'direct',
    'worker_timeout' => 300, // worker 超时退出（每次消费前检查）
];