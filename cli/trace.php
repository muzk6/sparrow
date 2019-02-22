<?php

/**
 * 开启条件自动 xdebug_trace
 *
 * 没有必要用 ip 过滤，
 * 需要登录的网页用 uid 过滤即可(开启后可以让用户触发 trace)，
 * 不需要登录的网页就由我自己来调试
 */

require_once dirname(__DIR__) . '/init.php';

$opt = getopt('', ['help::', 'uri::', 'uid::', 'expire::', 'off::',
    'max-depth::', 'max-data::', 'max-children::'], $ind);

$maxDepth = intval(ini_get('xdebug.var_display_max_depth'));
$maxData = intval(ini_get('xdebug.var_display_max_data'));
$maxChildren = intval(ini_get('xdebug.var_display_max_children'));

if (isset($opt['help'])) {
    echo <<<DOC
USAGE
    php trace.php [OPTION...] URI
PARAM
    URI
        Url path which trigger start xdebug trace.
OPTION
    --uid=
        UserId which has logined.
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
    echo 'Xdebug trace Off' . PHP_EOL;
    exit;
}

$uri = &$argv[$ind];
if (empty($uri)) {
    echo '缺少参数 URI' . PHP_EOL;
    exit;
}

$conf = [
    'uri' => $uri,
    'user_id' => $opt['uid'] ?? 0,
    'expire' => isset($opt['expire']) ? APP_TIME + $opt['expire'] : APP_TIME + 600,
    'max_depth' => $opt['max-depth'] ?? $maxDepth,
    'max_data' => $opt['max-data'] ?? $maxData,
    'max_children' => $opt['max-children'] ?? $maxChildren,
];

file_put_contents($traceConfFile,
    "<?php\nreturn " . var_export($conf, true) . ";\n");
print_r($conf);