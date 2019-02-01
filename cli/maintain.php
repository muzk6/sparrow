<?php

/**
 * 维护模式
 */

$opt = getopt('', ['help::']);
if (isset($opt['help'])) {
    echo <<<DOC
Switch maintenance mode.

USAGE
    php maintain.php [on|off]
PARAM
    on 
        (Default), Start maintenance mode, and website is now blocked.
    off
        Stop maintenance mode, and website is normal visit.
DOC;
    echo PHP_EOL;
    exit;
}

$stub = dirname(__DIR__) . '/data/down';
$mode = strtolower($argv[1] ?? 'on');
if ($mode === 'off') {
    is_file($stub) && unlink($stub);
    echo '维护模式已关闭' . PHP_EOL;
} else if ($mode === 'on') {
    touch($stub);
    echo '维护模式已开启' . PHP_EOL;
}