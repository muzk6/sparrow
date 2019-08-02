<?php

namespace Core;

use Exception;
use PhpAmqpLib\Channel\AMQPChannel;
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

    /**
     * @var AMQPStreamConnection
     */
    protected $connection;

    /**
     * @var AMQPChannel[]
     */
    protected $channels = [];

    public function __construct(array $conf)
    {
        if (!class_exists('\PhpAmqpLib\Connection\AMQPStreamConnection')) {
            trigger_error('"composer require php-amqplib/php-amqplib" at first');
        }

        $this->conf = $conf;
    }

    public function __destruct()
    {
        if ($this->connection) {
            $this->connection->close();
        }

        foreach ($this->channels as $channel) {
            $channel->close();
        }
    }

    /**
     * 初始化连接
     * @param $queue
     * @return AMQPChannel
     */
    protected function init($queue)
    {
        if (!$this->connection) {
            $this->connection = new AMQPStreamConnection($this->conf['host'], $this->conf['port'], $this->conf['user'], $this->conf['passwd']);
        }

        if (!isset($this->channels[$queue])) {
            $this->channels[$queue] = $this->connection->channel();
            $this->channels[$queue]->exchange_declare(strval($this->conf['exchange_name']), strval($this->conf['exchange_type']), false, false, false);
            $this->channels[$queue]->queue_declare($queue, false, true, false, false);
        }

        return $this->channels[$queue];
    }

    /**
     * 消息队列发布
     * @param string $queue 队列名
     * @param array $data
     */
    public function publish(string $queue, array $data)
    {
        $channel = $this->init($queue);
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

        $channel = $this->init($queue);
        $channel->basic_qos(null, 1, null);

        $channel->basic_consume($queue, '', false, false, false, false,
            function ($msg) use ($queue, $callback) {
                /** @var AMQPChannel $channel */
                $channel = $msg->delivery_info['channel'];

                $params = json_decode($msg->body, true);
                $startTime = microtime(true);

                echo PHP_EOL . PHP_EOL;
                echo $queue . PHP_EOL;
                echo str_repeat('-', 30) . PHP_EOL;
                echo 'Params: ' . PHP_EOL;
                var_export($params);
                echo PHP_EOL;

                try {
                    $result = $callback($params);
                    $channel->basic_ack($msg->delivery_info['delivery_tag']);

                    echo 'Result: ' . PHP_EOL;
                    var_export($result);
                } catch (Exception $exception) {
                    $channel->basic_nack($msg->delivery_info['delivery_tag']);

                    echo 'Exception: ' . PHP_EOL;
                    var_export($exception->getMessage());
                }
                echo PHP_EOL;

                $endTime = microtime(true);
                echo 'StartTime: ' . date('Y-m-d H:i:s', $startTime) . PHP_EOL;
                echo 'EndTime: ' . date('Y-m-d H:i:s', $endTime) . PHP_EOL;
                echo 'Elapse(s): ' . ($endTime - $startTime) . PHP_EOL;
            }
        );

        $fileStats = [];
        $startTime = time();
        while (count($channel->callbacks)) {
            if (time() - $startTime >= 300) {
                echo 'Exit: Timeout.' . PHP_EOL;
                exit;
            }

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
                    echo 'Exit: Files Update.' . PHP_EOL;
                    exit;
                }
            }

            $channel->wait();
        }
    }
}
