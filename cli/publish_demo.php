<?php

/**
 * 队列任务发布
 */

require_once dirname(__DIR__) . '/init.php';

app('app.queue')->publish('app_task', [date('Y-m-d H:i:s')]);
