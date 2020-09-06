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
            trigger_error('"pecl install msgpack && pecl install yar" at first', E_USER_ERROR);
        }

        $this->servers = $conf;
    }

    /**
     * 获取 RPC 地址
     * @param string $index
     * @return string
     */
    public function getUrl(string $index)
    {
        $url = $this->servers[$index] ?? $index;
        if ($this->traceName) {
            $url .= (strpos($url, '?') === false ? '?' : '&') . "_xt={$this->traceName}";
            $this->traceName = '';
        }

        return $url;
    }

    /**
     * 串行调用
     * @param string $server 配置名(位于 yar.php)，或者 URL 地址
     * @param string $action 服务端类方法名
     * @param array $params 请求参数
     * @param int $timeout 超时(ms)
     * @return mixed
     * @throws AppException
     * @see 例子参考 tests/feature/yar_client.php
     */
    public function request(string $server, string $action, array $params, int $timeout = 5000)
    {
        $url = $this->getUrl($server);
        $client = new \Yar_Client($url);
        $client->SetOpt(YAR_OPT_CONNECT_TIMEOUT, $timeout);

        try {
            return call_user_func_array([$client, $action], $params);
        } catch (Exception $exception) {
            if ($exception->getType() == 'Core\AppException') {
                throw new AppException($exception->getMessage(), $exception->getCode());
            } else {
                throw new $exception;
            }
        }
    }

    /**
     * 并行调用
     * @param string $server 配置名(位于 yar.php)，或者 URL 地址
     * @param string $action 服务端类方法名
     * @param array $params 请求参数
     * @param callable $callback($retVal, $callInfo) 正常回调函数
     * @param callable|null $errorCallback($type, $error, $callInfo) 错误时的回调函数
     * @param int $timeout 超时(ms)
     * @see 例子参考 tests/feature/yar_client.php
     */
    public function requestConcurrently(string $server, string $action, array $params, callable $callback, callable $errorCallback = null, int $timeout = 5000)
    {
        $url = $this->getUrl($server);
        \Yar_Concurrent_Client::call($url, $action, $params, $callback, $errorCallback,
            [YAR_OPT_TIMEOUT => $timeout]
        );
    }

    /**
     * RPC 并行调用 等待请求
     * @see https://github.com/laruence/yar/issues/26
     */
    public function loop()
    {
        \Yar_Concurrent_Client::loop();
        \Yar_Concurrent_Client::reset();
    }

    /**
     * RPC 服务端
     * @param Object $service 实例对象
     */
    public function server($service)
    {
        $server = new \Yar_Server($service);
        $server->handle();
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
