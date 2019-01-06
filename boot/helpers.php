<?php

use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

/**
 * 返回配置文件内容
 * @param string $filename 无后缀的文件名
 * @return array|null
 */
function app_config($filename)
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
function app_view()
{
    static $twig = null;

    if (!$twig) {
        $loader = new Twig_Loader_Filesystem(PATH_VIEW);
        $twig = new Twig_Environment($loader, [
            'cache' => PATH_DATA . '/compilation_cache',
        ]);
    }

    return $twig;
}

/**
 * 数据库
 */
function app_db()
{
    static $pdo = null;

    if (!$pdo) {
        $conf = app_config('database');
        $pdo = new PDO("mysql:dbname={$conf['dbname']};host={$conf['host']};port={$conf['port']}",
            $conf['username'], $conf['passwd']);
    }

    return $pdo;
}

/**
 * 日志
 * @param string $name 日志器名称，也是日志文件名前缀
 * @return Logger
 */
function app_log($name = 'app')
{
    static $logGroup = [];

    $log = &$logGroup[$name];
    if (!isset($log)) {
        $log = new Logger($name);

        $date = date('Ym');
        $log->pushHandler(new StreamHandler(PATH_DATA . "/log/{$name}_{$date}.log"));
    }

    return $log;
}

/**
 * redis缓存
 * @return Predis\Client
 */
function app_redis()
{
    static $client = null;

    if (!$client) {
        $conf = app_config('redis');
        $client = new Predis\Client([
            'scheme' => $conf['scheme'],
            'host' => $conf['host'],
            'port' => $conf['port'],
        ]);
    }

    return $client;
}

function app_rabbit_mq()
{
    $connection = new AMQPStreamConnection('localhost', 5672, 'guest', 'guest');
    $channel = $connection->channel();

    $channel->queue_declare('hello', false, false, false, false);

    $msg = new AMQPMessage('Hello World!');
    $channel->basic_publish($msg, '', 'hello');
    echo " [x] Sent 'Hello World!'\n";

//    $callback = function ($msg) {
//        echo ' [x] Received ', $msg->body, "\n";
//    };
//    $channel->basic_consume('hello', '', false, true, false, false, $callback);
//    while (count($channel->callbacks)) {
//        $channel->wait();
//    }

    $channel->close();
    $connection->close();
}