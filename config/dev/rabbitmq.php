<?php

/**
 * RabbitMQ 队列
 */

return [
    [
        'host' => 'rabbitmq',
        'port' => 5672,
        'user' => 'guest',
        'passwd' => 'guest',
        'vhost' => '/',
        'connection_timeout' => 3,
        'read_write_timeout' => 3,
    ],
];
