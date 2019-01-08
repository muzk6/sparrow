<?php

use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

/**
 * 配置文件
 * @param string $filename 无后缀的文件名
 * @return array|null 返回配置文件内容
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
 * 视图模板 twig
 * @return null|Twig_Environment
 */
function app_twig()
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
 * 数据库 pdo
 */
function app_pdo()
{
    static $pdo = null;

    if (!$pdo) {
        $conf = app_config('database');
        $pdo = new PDO("mysql:dbname={$conf['dbname']};host={$conf['host']};port={$conf['port']}",
            $conf['user'], $conf['passwd']);
    }

    return $pdo;
}

/**
 * 日志 monolog
 * @param string $name 日志器名称，也是日志文件名前缀
 * @return Logger
 */
function app_monolog($name = 'app')
{
    static $logGroup = [];

    $log = &$logGroup[$name];
    if (!isset($log)) {
        $log = new Logger($name);

        $path = sprintf('%s/%s_%s_%s.log',
            PATH_LOG, PHP_SAPI, $name, date('Ym'));
        $log->pushHandler(new StreamHandler($path));
    }

    return $log;
}

/**
 * 缓存 redis
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

/**
 * 消息队列发布
 * @param $queue
 * @param array $data
 */
function app_publish($queue, array $data)
{
    static $connection = null;
    static $channel = null;

    if (!$connection || !$channel) {
        $conf = app_config('rabbitmq');
        $connection = new AMQPStreamConnection($conf['host'], $conf['port'], $conf['user'], $conf['passwd']);

        $channel = $connection->channel();
        $channel->exchange_declare($conf['exchange_name'], $conf['exchange_type'], false, false, false);
        $channel->queue_declare($queue, false, true, false, false);
    }

    $msg = new AMQPMessage(
        json_encode($data),
        ['delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT]
    );

    $channel->basic_publish($msg, '', $queue);
}

/**
 * 消息队列消费
 * @param $queue
 * @param callable $callback
 */
function app_consume($queue, callable $callback)
{
    if (PHP_SAPI != 'cli') {
        return;
    }

    $conf = app_config('rabbitmq');
    $connection = new AMQPStreamConnection($conf['host'], $conf['port'], $conf['user'], $conf['passwd']);

    $channel = $connection->channel();
    $channel->exchange_declare($conf['exchange_name'], $conf['exchange_type'], false, false, false);
    $channel->queue_declare($queue, false, true, false, false);
    $channel->basic_qos(null, 1, null);

    $channel->basic_consume($queue, '', false, false, false, false,
        function ($msg) use ($callback) {
            $callback(json_decode($msg->body, true));
            $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
        });

    static $fileStats = [];
    while (count($channel->callbacks)) {
        $includedFiles = get_included_files();
        foreach ($includedFiles as $includedFile) {
            clearstatcache(true, $includedFile);
            $mtime = filemtime($includedFile);
            $size = filesize($includedFile); // 同时比较文件大小，防止开发机发运行环境时间不一致

            // 记录、检查文件最后修改时间、大小，不同就直接结束进程(使用 supervisor 进行重启)
            if (!isset($fileStats[$includedFile])) {
                $fileStats[$includedFile] = ['mtime' => $mtime, 'size' => $size];
            } elseif ($fileStats[$includedFile]['mtime'] != $mtime
                || $fileStats[$includedFile]['size'] != $size) {
                $channel->close();
                $connection->close();
                exit;
            }
        }

        $channel->wait();
    }

    $channel->close();
    $connection->close();
}