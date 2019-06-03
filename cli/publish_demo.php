<?php

/**
 * 队列任务发布
 */

require_once dirname(__DIR__) . '/init.php';

inject(function (\Core\Queue $appQueue) {
    $appQueue->publish('app_task', [date('Y-m-d H:i:s')]);
});
