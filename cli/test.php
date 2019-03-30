<?php

/**
 * php-cli 测试文件
 * php test.php
 */

use Core\AppException;

require_once dirname(__DIR__) . '/init.php';

try {
    //todo...
} catch (AppException $exception) {
    var_dump(format2api($exception));
}
