<?php

/**
 * 队列任务 worker
 */

require_once dirname(__DIR__) . '/init.php';

app(\Core\AppQueue::class)->consume('app_task', function ($data) {
    var_dump($data);
});
