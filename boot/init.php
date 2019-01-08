<?php

define('PATH_ROOT', dirname(__DIR__));
define('PATH_VIEW', PATH_ROOT . '/views');
define('PATH_PUBLIC', PATH_ROOT . '/public');
define('PATH_DATA', PATH_ROOT . '/data');
define('PATH_LOG', PATH_DATA . '/log');
define('PATH_CONFIG', PATH_ROOT . '/config');

if (is_file('/www/PUB')) {
    ini_set('display_errors', 0);
    define('APP_ENV', 'pub');
} else {
    ini_set('display_errors', 1);
    define('APP_ENV', 'dev');
}

define('PATH_CONFIG_ENV', PATH_ROOT . '/config/' . APP_ENV);

ini_set('log_errors', 1);
ini_set('error_log', sprintf('%s/%s_error_%s.log', PATH_LOG, PHP_SAPI, date('Ym')));

require_once PATH_ROOT . '/vendor/autoload.php';