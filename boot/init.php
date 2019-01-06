<?php

define('PATH_ROOT', dirname(__DIR__));
define('PATH_VIEW', PATH_ROOT . '/views');
define('PATH_PUBLIC', PATH_ROOT . '/public');
define('PATH_DATA', PATH_ROOT . '/data');
define('PATH_CONFIG', PATH_ROOT . '/config');

if (is_file('/www/PUB')) {
    ini_set('display_errors', 0);
    define('APP_ENV', 'pub');
} else {
    ini_set('display_errors', 1);
    define('APP_ENV', 'dev');
}

define('PATH_CONFIG_ENV', PATH_ROOT . '/config/' . APP_ENV);

require_once PATH_ROOT . '/vendor/autoload.php';

set_error_handler(function ($errno, $errstr, $errfile, $errline, $errcontext) {
    var_dump($errstr);
});

//register_shutdown_function(function () {
//    $error = error_get_last();
//    var_dump($error);
//});