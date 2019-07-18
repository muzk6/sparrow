<?php

/**
 * worker 示例
 * 建议规则：
 * 1. 每个 worker 只消费一个队列；
 * 2. 队列名与 worker 名一致，便于定位队列名对应的 worker 文件；
 * 3. 队列名/worker名 要有项目名前缀，防止在 Supervisor, RabbitMq 里与其它项目搞混
 */

use Core\Queue;

require_once dirname(__DIR__) . '/init.php';

app(Queue::class)->consume('SPARROW_QUEUE_DEMO', function ($params) {
    return $params;
});
