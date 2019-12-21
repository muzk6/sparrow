<?php


namespace Core;


use Exception;

/**
 * RPC Yar
 * @package Core
 */
class Yar
{
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

        $this->servers = $conf;
    }

    /**
     * 返回 RPC地址
     * @param string $index
     * @return string
     */
    protected function getUrl(string $index)
    {
        $server = &$this->servers[$index];
        isset($server) || trigger_error('请在 yar.php 配置相关地址');

        $url = $server['url'];
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
     * @return mixed
     * @see 例子参考 cli/rpc_client_demo.php
     */
    public function request(string $server, string $action, array $params, int $timeout = 3000, int $retry = 3)
    {
        $url = $this->getUrl($server);
        $client = new \Yar_Client($url);
        $client->SetOpt(YAR_OPT_CONNECT_TIMEOUT, $timeout);

        for ($i = 1; $i <= $retry; $i++) {
            try {
                $result = call_user_func_array([$client, $action], $params);
                if ($result !== null) {
                    return $result;
                }
            } catch (Exception $exception) {
                logfile('Yar_Client::call', [
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
     * 并行调用
     * @param string $server 服务端接口名，yar.php 里的 servers项
     * @param string $action
     * @param array $params
     * @param callable $callback 回调函数
     * @param int $timeout 超时(ms)
     * @see 例子参考 cli/rpc_client_demo.php
     */
    public function requestConcurrently(string $server, string $action, array $params, callable $callback, int $timeout = 3000)
    {
        $url = $this->getUrl($server);
        try {
            \Yar_Concurrent_Client::call($url, $action, [$params], $callback, null,
                [YAR_OPT_TIMEOUT => $timeout]
            );
        } catch (Exception $exception) {
            logfile('Yar_Concurrent_Client::call', [
                'message' => $exception->getMessage(),
                'server' => $server,
                'action' => $action,
                'params' => $params,
                'timeout' => $timeout,
            ], '__rpc');
        }
    }

    /**
     * RPC 并行调用 等待请求
     * @see https://github.com/laruence/yar/issues/26
     */
    public function loop()
    {
        try {
            \Yar_Concurrent_Client::loop();
            \Yar_Concurrent_Client::reset();
        } catch (Exception $exception) {
            logfile('\Yar_Concurrent_Client::loop', $exception->getMessage(), '__rpc');
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

            /** @var Xdebug $xdebug */
            $xdebug = app(Xdebug::class);

            $xdebug->auto();
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
