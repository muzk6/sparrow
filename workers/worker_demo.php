<?php

/**
 * 队列任务 worker
 */

require_once dirname(__DIR__) . '/boot/init.php';

app_consume('app_task', function ($data) {
    var_dump($data);
});