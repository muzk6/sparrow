<?php

namespace Core;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

/**
 * 消息队列
 * @package Core
 */
class Queue
{
    /**
     * 配置
     * @var array
     */
    protected $conf;

    public function __construct(array $conf)
    {
        if (!class_exists('\PhpAmqpLib\Connection\AMQPStreamConnection')) {
            trigger_error('"composer require php-amqplib/php-amqplib" at first');
        }

        $this->conf = $conf;
    }

    /**
     * 消息队列发布
     * @param string $queue 队列名
     * @param array $data
     */
    public function publish(string $queue, array $data)
    {
        static $connection = null;
        static $channel = null;

        if (!$connection || !$channel) {
            $connection = new AMQPStreamConnection($this->conf['host'], $this->conf['port'], $this->conf['user'], $this->conf['passwd']);

            $channel = $connection->channel();
            $channel->exchange_declare(strval($this->conf['exchange_name']), strval($this->conf['exchange_type']), false, false, false);
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
     * @param string $queue 队列名
     * @param callable $callback
     * @throws \ErrorException
     */
    public function consume(string $queue, callable $callback)
    {
        if (PHP_SAPI != 'cli') {
            return;
        }

        $connection = new AMQPStreamConnection($this->conf['host'], $this->conf['port'], $this->conf['user'], $this->conf['passwd']);

        $channel = $connection->channel();
        $channel->exchange_declare(strval($this->conf['exchange_name']), strval($this->conf['exchange_type']), false, false, false);
        $channel->queue_declare($queue, false, true, false, false);
        $channel->basic_qos(null, 1, null);

        $channel->basic_consume($queue, '', false, false, false, false,
            function ($msg) use ($queue, $callback) {
                $params = json_decode($msg->body, true);
                $startTime = microtime(true);

                echo PHP_EOL . PHP_EOL;
                echo $queue . PHP_EOL;
                echo str_repeat('-', 30) . PHP_EOL;
                echo 'Params: ' . PHP_EOL;
                print_r($params);

                $result = $callback($params);

                echo PHP_EOL;
                echo 'Result: ' . PHP_EOL;
                print_r($result);

                $endTime = microtime(true);
                echo PHP_EOL;
                echo 'StartTime: ' . date('Y-m-d H:i:s', $startTime) . PHP_EOL;
                echo 'EndTime: ' . date('Y-m-d H:i:s', $endTime) . PHP_EOL;
                echo 'Elapse(s): ' . ($endTime - $startTime) . PHP_EOL;

                $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
            });

        static $fileStats = [];
        while (count($channel->callbacks)) {
            $includedFiles = get_included_files();
            foreach ($includedFiles as $includedFile) {
                clearstatcache(true, $includedFile);
                $mtime = filemtime($includedFile);
                $size = filesize($includedFile); // 同时比较文件大小，防止开发机与运行环境时间不一致

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
}
