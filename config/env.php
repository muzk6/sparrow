<?php

/**
 * 环境配置
 */

$displayErrors = 0;

if (is_file('/www/.pub.env')) { // 生产环境 publish
    define('APP_ENV', 'pub');
    define('PATH_CONFIG_ENV', PATH_ROOT . '/config/pub');

    error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);

} else { // 开发环境 develop
    define('APP_ENV', 'dev');
    define('PATH_CONFIG_ENV', PATH_ROOT . '/config/dev');

    error_reporting(E_ALL);
    $displayErrors = 1;

}

if (PHP_SAPI == 'cli') {
    $displayErrors = 1;
}

ini_set('display_errors', $displayErrors);
