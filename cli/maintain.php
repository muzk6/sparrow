<?php

/**
 * 维护模式
 * 0.关闭 1.开启
 */

$stub = dirname(__DIR__) . '/data/down';
$mode = intval($argv[1]);
if ($mode === 0) {
    is_file($stub) && unlink($stub);
    echo '维护模式已关闭' . PHP_EOL;
} else if ($mode === 1) {
    touch($stub);
    echo '维护模式已开启' . PHP_EOL;
}