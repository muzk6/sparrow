<?php

use Core\Xdebug;

define('PATH_ROOT', __DIR__);
define('PATH_APP', PATH_ROOT . '/app');
define('PATH_VIEW', PATH_ROOT . '/views');
define('PATH_PUBLIC', PATH_ROOT . '/public');
define('PATH_DATA', PATH_ROOT . '/data');
define('PATH_LOG', PATH_DATA . '/log');
define('PATH_TRACE', PATH_DATA . '/trace');
define('PATH_CONFIG', PATH_ROOT . '/config');
define('PATH_LANG', PATH_ROOT . '/lang');
define('TIME', $_SERVER['REQUEST_TIME']); // 注意：不能在 worker 里使用，否则不会变化

// 环境与通用配置文件
if (file_exists(PATH_CONFIG . '/env.php')) {
    require PATH_CONFIG . '/env.php';
} else {
    if (is_file('/www/PUB')) { // publish
        define('APP_ENV', 'pub');
        error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
        ini_set('display_errors', 0);
    } else { // develop
        define('APP_ENV', 'dev');
        error_reporting(E_ALL);
        ini_set('display_errors', 1);
    }
}

// 指定环境的配置文件
define('PATH_CONFIG_ENV', PATH_ROOT . '/config/' . APP_ENV);
if (!file_exists(PATH_CONFIG_ENV)) {
    echo '缺少配置: ' . PATH_CONFIG_ENV;
    exit;
}

define('IS_DEV', APP_ENV == 'dev');
define('IS_POST', isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'POST');
define('IS_GET', isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'GET');
define('IS_OPTIONS', isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'OPTIONS');

// 日志
ini_set('log_errors', 'On');
ini_set('error_log', sprintf('%s/__error_%s.log', PATH_LOG, date('ymd')));
if (!file_exists(PATH_LOG)) {
    mkdir(PATH_LOG, 0777, true);
}

// 优先加载自己的 helpers
require_once PATH_ROOT . '/core/helpers.php';

// composer
require_once PATH_ROOT . '/vendor/autoload.php';

// 配置文件里的默认配置
$appConf = config('app');
date_default_timezone_set($appConf['timezone']);

// cookie 里的 lang 没设置或不支持时，就使用配置文件里的默认语言
define('APP_LANG', isset($_COOKIE['lang'])
    ? (is_file(sprintf('%s/%s.php', PATH_LANG, $_COOKIE['lang']))
        ? $_COOKIE['lang'] : $appConf['lang'])
    : $appConf['lang']
);

if (PHP_SAPI != 'cli') {
    // session
    $sessionConf = config('session');
    if ($sessionConf['session.save_handler'] == 'files' && !file_exists($sessionConf['session.save_path'])) {
        mkdir($sessionConf['session.save_path'], 0777, true);
    }
    foreach ($sessionConf as $k => $v) {
        ini_set($k, $v);
    }
    session_id() || session_start();
} else {
    if (isset($_SERVER['argv']) && in_array('--trace', $_SERVER['argv'])) {
        /** @var Xdebug $xdebug */
        $xdebug = app(Xdebug::class);
        $xdebug->start('cli');
    }
}
