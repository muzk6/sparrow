<?php

/**
 * 返回配置文件内容
 * @param string $filename 无后缀的文件名
 * @return array|null
 */
function config($filename)
{
    if (is_file($path = PATH_CONFIG . "/{$filename}.php")) {
        return include($path);
    } else if (is_file($path = PATH_CONFIG_ENV . "/{$filename}.php")) {
        return include($path);
    }

    return null;
}

/**
 * 视图模板
 * @return null|Twig_Environment
 */
function view()
{
    static $twig = null;

    if (!$twig) {
        $loader = new Twig_Loader_Filesystem(PATH_VIEW);
        $twig = new Twig_Environment($loader, array(
            'cache' => PATH_DATA . '/compilation_cache',
        ));
    }

    return $twig;
}

/**
 * 数据库
 */
function db()
{
    static $pdo = null;

    if (!$pdo) {
        $conf = config('database');
        $pdo = new PDO("mysql:dbname={$conf['dbname']};host={$conf['host']};port={$conf['port']}",
            $conf['username'], $conf['passwd']);
    }

    return $pdo;
}