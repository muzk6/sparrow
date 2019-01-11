<?php

define('PATH_ROOT', dirname(__DIR__));
define('PATH_VIEW', PATH_ROOT . '/views');
define('PATH_PUBLIC', PATH_ROOT . '/public');
define('PATH_DATA', PATH_ROOT . '/data');
define('PATH_LOG', PATH_DATA . '/log');
define('PATH_CONFIG', PATH_ROOT . '/config');
define('PATH_LANG', PATH_ROOT . '/lang');

// 环境与配置文件
if (is_file('/www/PUB')) { // publish
    ini_set('display_errors', 0);
    define('APP_ENV', 'pub');
} else {
    ini_set('display_errors', 1); // develop
    define('APP_ENV', 'dev');
}
define('PATH_CONFIG_ENV', PATH_ROOT . '/config/' . APP_ENV);

// 日志
ini_set('log_errors', 1);
ini_set('error_log', sprintf('%s/%s_error_%s.log', PATH_LOG, PHP_SAPI, date('Ym')));

// composer
require_once PATH_ROOT . '/vendor/autoload.php';

// 配置文件里的默认配置
$appConf = app_config('app');
date_default_timezone_set($appConf['timezone']);

// cookie里的 lang 没设置或不支持时，就使用配置文件里的默认语言
define('APP_LANG', isset($_COOKIE['lang'])
    ? (is_file(sprintf('%s/%s.php', PATH_LANG, $_COOKIE['lang']))
        ? $_COOKIE['lang'] : $appConf['lang'])
    : $appConf['lang']
);

// session
$sessionConf = app_config('session');
if ($sessionConf['session.save_handler'] == 'files' && !file_exists($sessionConf['session.save_path'])) {
    mkdir($sessionConf['session.save_path'], 0777, true);
}
foreach ($sessionConf as $k => $v) {
    ini_set($k, $v);
}
session_id() || session_start();