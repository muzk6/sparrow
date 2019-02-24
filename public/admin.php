<?php

/**
 * 后台入口文件
 * 必须使用另一个域名指向此文件
 */

require_once dirname(__DIR__) . '/init.php';

define('CONTROLLER_NAMESPACE', 'App\Controllers\Admin\\');
require_once PATH_ROOT . '/core/router.php';