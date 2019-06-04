<?php


namespace Core;


use Exception;

/**
 * RPC Yar
 * @package Core
 */
class Yar
{
    protected $hosts = [];

    protected $servers = [];

    protected $traceName = '';

    /**
     * @param array $conf 配置
     */
    public function __construct(array $conf)
    {
        if (!class_exists('\Yar_Client')) {
            trigger_error('"pecl install msgpack && pecl install yar" at first');
        }

        $this->hosts = $conf['hosts'];
        $this->servers = $conf['servers'];
    }

    /**
     * 返回 RPC地址
     * @param string $index
     * @return string
     */
    protected function parseUrl(string $index): string
    {
        $rpc = &$this->servers[$index];
        isset($rpc) || trigger_error('请在 yar.php 配置 servers');

        $host = &$this->hosts[$rpc['host']];
        isset($host) || trigger_error('请在 yar.php 配置 hosts');

        $url = $host . $rpc['uri'];
        if ($this->traceName) {
            $url .= (strpos($url, '?') === false ? '?' : '&') . "_xt={$this->traceName}";
            $this->traceName = '';
        }

        return $url;
    }

    /**
     * 串行调用
     * @param string $server 服务端接口名，yar.php 里的 servers项
     * @param string $action 接口的动作名
     * @param array $params 请求参数
     * @param int $timeout 超时(ms)
     * @param int $retry 重试次数
     * @return AppYarClient
     * @see 例子参考 cli/rpc_client_demo.php
     */
    public function request(string $server, string $action, array $params, $timeout = 3000, $retry = 3)
    {
        $url = $this->parseUrl($server);
        $client = new \Yar_Client($url);
        $client->SetOpt(YAR_OPT_CONNECT_TIMEOUT, $timeout);

        for ($i = 1; $i <= $retry; $i++) {
            try {
                return call_user_func_array([$client, $action], [$params]);
            } catch (Exception $exception) {
                logfile('client_call', [
                    'message' => $exception->getMessage(),
                    'server' => $server,
                    'action' => $action,
                    'params' => $params,
                    'timeout' => $timeout,
                    'retry' => "{$i}/{$retry}",
                ], '__rpc');
            }
        }

        return null;
    }

    /**
     * 并行调用客户端
     * @param string $serverName 服务端接口名，yar.php 里的 servers项
     * @param string|callable $callback 回调函数
     * @param int $timeout 超过时间(秒)
     * @return AppYarConcurrentClient
     * @see 例子参考 cli/rpc_client_demo.php
     */
    public function concurrentClient(string $serverName, $callback, $timeout = 3000)
    {
        $url = $this->parseUrl($serverName);
        return new AppYarConcurrentClient($url, $callback, $timeout);
    }

    /**
     * RPC 并行调用 等待请求
     * @param bool $reset 调用完后清除所有回调，其作用参考下面的链接
     * @see https://github.com/laruence/yar/issues/26
     */
    public function concurrentLoop($reset = true)
    {
        try {
            \Yar_Concurrent_Client::loop();
            $reset && \Yar_Concurrent_Client::reset();
        } catch (Exception $exception) {
            logfile('concurrent_loop', $exception->getMessage(), '__rpc');
        }
    }

    /**
     * RPC 服务端
     * @param string $class 接口的类名
     * @see 例子参考 rpc/rpc_server_demo.php
     */
    public function server(string $class)
    {
        try {
            $server = new \Yar_Server(app($class));

            app(Xdebug::class)->auto();
            $server->handle();
        } catch (Exception $exception) {
            logfile('server_handle', $exception->getMessage(), '__rpc');
        }
    }

    /**
     * 仅此次开启 Xdebug Trace 跟踪
     * @param string $traceName 日志名，即日志文件名的 xt: 的值
     * @return $this
     * @see 用法参考 \Core\AppXdebug::trace
     */
    public function trace(string $traceName)
    {
        $this->traceName = $traceName;
        return $this;
    }

}

/**
 * Yar 串行调用的客户端
 * @package Core
 */
class AppYarClient
{
    protected $client;

    public function __construct(\Yar_Client $client)
    {
        $this->client = $client;
    }

    public function __call($name, $arguments)
    {
        try {
            return call_user_func_array([$this->client, $name], $arguments);
        } catch (Exception $exception) {
            logfile('client_call', $exception->getMessage(), '__rpc');
            return null;
        }
    }
}

/**
 * Yar 并行调用的客户端
 * @package Core
 */
class AppYarConcurrentClient
{
    protected $url;
    protected $callback;
    protected $timeout;

    public function __construct(string $url, $callback, $timeout)
    {
        $this->url = $url;
        $this->callback = $callback;
        $this->timeout = $timeout;
    }

    public function __call($name, $arguments)
    {
        try {
            \Yar_Concurrent_Client::call($this->url, $name, $arguments, $this->callback, null,
                [YAR_OPT_TIMEOUT => $this->timeout]
            );
        } catch (Exception $exception) {
            logfile('concurrent_call', $exception->getMessage(), '__rpc');
        }
    }
}
