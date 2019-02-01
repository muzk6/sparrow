<?php

/**
 * 维护模式
 */

$opt = getopt('', ['help::']);
if (isset($opt['help'])) {
    echo <<<DOC
维护模式

USAGE
    php maintain.php <on|off>
PARAM
    on 开启维护模式，此时网站禁止访问
    off 关闭维护模式，此时网站正常访问
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