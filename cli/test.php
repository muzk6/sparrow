<?php

/**
 * php-cli 测试文件
 * php test.php
 */

require_once dirname(__DIR__) . '/init.php';

inject(function () {
    try {
        //todo...
    } catch (\Core\AppException $appException) {
        var_dump(api_format($appException));
    }
});
