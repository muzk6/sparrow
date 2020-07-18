<?php

/**
 * 按条件自动开启 Xdebug Trace
 *
 * 没有必要用 ip 过滤
 * 需要登录的网页用 uid 过滤即可(开启后可以让用户触发 trace)
 * 不需要登录的网页就由开发者自己用 url 的 ?_xt=name0 方式来调试
 */

require dirname(__DIR__) . '/init.php';

$opt = getopt('', ['help::', 'url::', 'uid::', 'name::', 'expire::', 'off::',
    'max-depth::', 'max-data::', 'max-children::'], $ind);

$maxDepth = intval(ini_get('xdebug.var_display_max_depth'));
$maxData = intval(ini_get('xdebug.var_display_max_data'));
$maxChildren = intval(ini_get('xdebug.var_display_max_children'));

if (isset($opt['help'])) {
    echo <<<DOC
USAGE
    php trace.php [OPTION...] NAME URL
PARAM
    NAME
        Name Of Xdebug Trace as xt: segment in log name.
    URL
        Url path which trigger start xdebug trace.
OPTION
    --uid=
        User ID which has logined.
    --expire= (Default 10min)
        Expire after N seconds.
        --expire=60 (After 60s expire.)
    --off
        Turn off xdebug trace.
    --max-depth={$maxDepth} (Default)
        Controls how many nested levels of array elements and object properties are when variables are displayed.
    --max-data={$maxData} (Default)
        Controls the maximum string length that is shown when variables are displayed.
    --max-children={$maxChildren} (Default)
        Controls the amount of array children and object's properties are shown when variables are displayed.
DOC;
    echo PHP_EOL;
    exit;
}

$traceConfFile = PATH_DATA . '/.tracerc';
if (isset($opt['off'])) {
    file_exists($traceConfFile) && unlink($traceConfFile);
    echo 'Xdebug Trace is Off' . PHP_EOL;
    exit;
}

$name = &$argv[$ind++];
if (empty($name)) {
    echo 'Require NAME' . PHP_EOL;
    exit;
}

$url = &$argv[$ind++];
if (empty($url)) {
    echo 'Require URL' . PHP_EOL;
    exit;
}

$conf = [
    'url' => $url,
    'user_id' => $opt['uid'] ?? 0,
    'name' => $name,
    'expire' => isset($opt['expire']) ? TIME + $opt['expire'] : TIME + 600,
    'max_depth' => $opt['max-depth'] ?? $maxDepth,
    'max_data' => $opt['max-data'] ?? $maxData,
    'max_children' => $opt['max-children'] ?? $maxChildren,
];

file_put_contents($traceConfFile,
    "<?php\nreturn " . var_export($conf, true) . ";\n");

echo 'Xdebug Trace is ON.' . PHP_EOL;
var_export($conf);
