<?php

/**
 * php-cgi 测试入口
 * http://{HOST}/test.php
 */

use Core\Whitelist;

require dirname(__DIR__) . '/init.php';

app(Whitelist::class)->checkSafeIpOrExit();

//todo..
