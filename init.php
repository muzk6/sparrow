<?php

use Core\ErrorHandler;
use Core\Xdebug;

define('PATH_ROOT', __DIR__);
define('PATH_APP', PATH_ROOT . '/app');
define('PATH_ROUTES', PATH_APP . '/Routes');
define('PATH_VIEW', PATH_ROOT . '/views');
define('PATH_PUBLIC', PATH_ROOT . '/public');
define('PATH_DATA', PATH_ROOT . '/data');
define('PATH_LOG', PATH_DATA . '/log');
define('PATH_TRACE', PATH_DATA . '/trace');
define('PATH_CONFIG', PATH_ROOT . '/config');
define('PATH_LANG', PATH_ROOT . '/lang');
define('TIME', $_SERVER['REQUEST_TIME']); // 注意：不能在 worker 里使用，否则不会变化

// 环境配置
require PATH_CONFIG . '/env.php';

if (!file_exists(PATH_CONFIG_ENV)) {
    trigger_error(PATH_CONFIG_ENV  . ' 配置目录不存在');
    exit;
}

define('IS_DEV', APP_ENV == 'dev');
define('IS_POST', isset($_SERVER['REQUEST_METHOD']) && strtoupper($_SERVER['REQUEST_METHOD']) == 'POST');
define('IS_GET', isset($_SERVER['REQUEST_METHOD']) && strtoupper($_SERVER['REQUEST_METHOD']) == 'GET');
define('IS_OPTIONS', isset($_SERVER['REQUEST_METHOD']) && strtoupper($_SERVER['REQUEST_METHOD']) == 'OPTIONS');

// 精简错误日志，能记录 Fatal Error, Parse Error
ini_set('log_errors', 1);
ini_set('error_log', sprintf('%s/unhandled_%s.log', PATH_LOG, date('ym')));
if (!file_exists(PATH_LOG)) {
    mkdir(PATH_LOG, 0777, true);
}

// 优先加载自己的 helpers
require PATH_ROOT . '/core/helpers.php';

// composer
require PATH_ROOT . '/vendor/autoload.php';

// 详细错误日志
set_error_handler([app(ErrorHandler::class), 'errorHandler']);
// 未捕获的异常日志
if (!app(ErrorHandler::class)->is_display_errors()) {
    set_exception_handler([app(ErrorHandler::class), 'exceptionHandler']);
}

// 配置文件里的默认配置
$appConf = config('app');
date_default_timezone_set($appConf['timezone']);

// cookie 里的 lang 没设置或不支持时，就使用配置文件里的默认语言
define('APP_LANG', isset($_COOKIE['lang'])
    ? (is_file(sprintf('%s/%s.php', PATH_LANG, $_COOKIE['lang']))
        ? $_COOKIE['lang'] : $appConf['lang'])
    : $appConf['lang']
);

if (PHP_SAPI != 'cli') { // fpm 模式
    // session
    $sessionConf = config('session');
    if ($sessionConf['session.save_handler'] == 'files' && !file_exists($sessionConf['session.save_path'])) {
        mkdir($sessionConf['session.save_path'], 0777, true);
    }
    foreach ($sessionConf as $k => $v) {
        ini_set($k, $v);
    }
    session_id() || session_start();
} else { // cli 模式
    // xdebug trace
    if (isset($_SERVER['argv']) && in_array('--trace', $_SERVER['argv'])) {
        app(Xdebug::class)->start('cli');
    }
}
