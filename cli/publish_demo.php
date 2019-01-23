<?php

/**
 * 队列任务发布
 */

require_once dirname(__DIR__) . '/boot/init.php';

queue()->publish('app_task', [date('Y-m-d H:i:s')]);