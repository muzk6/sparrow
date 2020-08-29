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

        if (!$this->connection) {
            shuffle($conf);
            foreach ($conf as $host) {
                try {
                    $this->connection = new AMQPStreamConnection($host['host'], $host['port'], $host['user'], $host['passwd']);
                } catch (\Exception $exception) {
                    trigger_error($exception->getMessage() . ': ' . json_encode($host, JSON_UNESCAPED_SLASHES));
                }
            }
        }
    }

    public function __destruct()
    {
        foreach ($this->channels as $channel) {
            $channel->close();
        }

        if ($this->connection) {
            $this->connection->close();
        }
    }

    /**
     * 消息队列发布
     * @param string $queue 队列名称
     * @param array $data
     * @param string $exchangeName 交换器名称
     * @param string $exchangeType 交换器类型
     */
    public function publish(string $queue, array $data, string $exchangeName = 'default.direct', string $exchangeType = 'direct')
    {
        $channelKey = md5("publish_{$exchangeName}_{$exchangeType}_{$queue}");
        if (!isset($this->channels[$channelKey])) {
            $this->channels[$channelKey] = $this->connection->channel();
            $this->channels[$channelKey]->exchange_declare($exchangeName, $exchangeType, false, true, false);
            $this->channels[$channelKey]->queue_declare($queue, false, true, false, false);
        }

        $msg = new AMQPMessage(
            json_encode($data),
            ['delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT]
        );

        $this->channels[$channelKey]->basic_publish($msg, '', $queue);
    }

    /**
     * 消息队列消费
     * @param string $queue 队列名称
     * @param callable $callback
     * @throws \ErrorException
     */
    public function consume(string $queue, callable $callback)
    {
        if (PHP_SAPI != 'cli') {
            return;
        }

        ini_set('memory_limit', -1);

        $channelKey = md5("consume_{$queue}");
        if (!isset($this->channels[$channelKey])) {
            $this->channels[$channelKey] = $this->connection->channel();
            $this->channels[$channelKey]->queue_declare($queue, false, true, false, false);
        }

        $this->channels[$channelKey]->basic_qos(null, 1, null);

        $scriptTime = time();
        $fileStats = [];
        $this->channels[$channelKey]->basic_consume($queue, '', false, false, false, false,
            function ($msg) use ($queue, $callback, $scriptTime, &$fileStats) {
                // 每300秒退出 worker, 比销毁容器更安全(考虑到开发者可能用静态类)，释放 mysql 之类的长连接
                if (time() - $scriptTime >= 300) {
                    echo sprintf('[Exit At %s, Timeout.]', date('Y-m-d H:i:s')) . PHP_EOL;
                    exit;
                }

                foreach ($fileStats as $file => $fileStat) {
                    // 检查文件最后修改时间、大小，不同就直接结束进程(使用 supervisor 进行重启)；同时比较文件大小，防止开发机与运行环境时间不一致
                    clearstatcache(true, $file);
                    if ($fileStat['mtime'] != filemtime($file)
                        || $fileStat['size'] != filesize($file)) {
                        echo sprintf('[Exit At %s, Files Updated.]', date('Y-m-d H:i:s')) . PHP_EOL;
                        exit;
                    }
                }

                /** @var AMQPChannel $channel */
                $channel = $msg->delivery_info['channel'];

                $params = json_decode($msg->body, true);
                $startTime = microtime(true);

                $tempId = uniqid();
                echo str_repeat('-', 30) . "<{$queue} id={$tempId}>" . str_repeat('-', 30) . PHP_EOL;
                echo 'Params: ' . PHP_EOL;
                var_export($params);
                echo PHP_EOL;

                try {
                    $result = $callback($params);
                    $channel->basic_ack($msg->delivery_info['delivery_tag']);

                    echo 'Result: ' . PHP_EOL;
                    var_export($result);
                    echo PHP_EOL;
                } catch (Exception $exception) {
                    $channel->basic_nack($msg->delivery_info['delivery_tag']);

                    echo 'Exception: ' . PHP_EOL;
                    var_export($exception->getMessage());
                }

                $endTime = microtime(true);
                echo 'StartTime: ' . date('Y-m-d H:i:s', $startTime);
                echo '; EndTime: ' . date('Y-m-d H:i:s', $endTime);
                echo '; Elapse(sec): ' . ($endTime - $startTime);
                echo '; PeakMemory(MB): ' . (memory_get_peak_usage(true) / 1024 / 1024) . PHP_EOL;
                echo str_repeat('-', 29) . "</{$queue} id={$tempId}>" . str_repeat('-', 30) . PHP_EOL;

                // 执行完业务 $callback 后，get_included_files() 才能取到所有相关文件，并及时保存文件状态
                $includedFiles = get_included_files();
                foreach ($includedFiles as $includedFile) {
                    clearstatcache(true, $includedFile);
                    $mtime = filemtime($includedFile);
                    $size = filesize($includedFile);

                    if (!isset($fileStats[$includedFile])) {
                        $fileStats[$includedFile] = ['mtime' => $mtime, 'size' => $size];
                    }
                }
            }
        );

        // 注册进程信号，防止 worker 中途被强制结束
        $signalHandler = function ($signal) {
            $map = array(
                SIGTERM => 'SIGTERM',
                SIGHUP => 'SIGHUP',
                SIGINT => 'SIGINT',
                SIGQUIT => 'SIGQUIT',
            );
            $signalName = $map[$signal] ?? $signal;

            echo sprintf("[Exit Softly At %s, By Signal: {$signalName}.]", date('Y-m-d H:i:s')) . PHP_EOL;
            exit;
        };

        pcntl_signal(SIGTERM, $signalHandler); // supervisor stop/restart 使用的信号
        pcntl_signal(SIGHUP, $signalHandler);
        pcntl_signal(SIGINT, $signalHandler);
        pcntl_signal(SIGQUIT, $signalHandler);

        while ($this->channels[$channelKey]->is_consuming()) {
            $this->channels[$channelKey]->wait();
            pcntl_signal_dispatch();
        }
    }
}
