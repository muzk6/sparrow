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
     * 获取 RPC 地址
     * @param string $index
     * @return string
     */
    protected function getUrl(string $index)
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
     * @param string $action 接口的动作名
     * @param array $params 请求参数
     * @param int $timeout 超时(ms)
     * @param int $retry 重试次数
     * @return mixed
     * @throws AppException
     * @see 例子参考 tests/feature/yar_client.php
     */
    public function request(string $server, string $action, array $params, int $timeout = 5000, int $retry = 1)
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
            } catch (\Exception $exception) {
                if ($exception->getType() == 'Core\AppException') {
                    throw new AppException($exception->getMessage(), $exception->getCode());
                } else {
                    $log = [
                        'message' => $exception->getMessage(),
                        'server' => $server,
                        'action' => $action,
                        'params' => $params,
                        'timeout' => $timeout,
                        'retry' => "{$i}/{$retry}",
                    ];
                    logfile('Yar::request', $log, 'error');

                    if (IS_DEV) {
                        trigger_error(json_encode($log, JSON_UNESCAPED_SLASHES), E_USER_WARNING);
                    }
                }
            }
        }

        return null;
    }

    /**
     * 并行调用
     * @param string $server 配置名(位于 yar.php)，或者 URL 地址
     * @param string $action
     * @param array $params
     * @param callable $callback 回调函数
     * @param int $timeout 超时(ms)
     * @throws AppException
     * @see 例子参考 tests/feature/yar_client.php
     */
    public function requestConcurrently(string $server, string $action, array $params, callable $callback, int $timeout = 5000)
    {
        $url = $this->getUrl($server);
        try {
            \Yar_Concurrent_Client::call($url, $action, [$params], $callback, null,
                [YAR_OPT_TIMEOUT => $timeout]
            );
        } catch (\Exception $exception) {
            if ($exception->getType() == 'Core\AppException') {
                throw new AppException($exception->getMessage(), $exception->getCode());
            } else {
                $log = [
                    'message' => $exception->getMessage(),
                    'server' => $server,
                    'action' => $action,
                    'params' => $params,
                    'timeout' => $timeout,
                ];
                logfile('Yar::requestConcurrently', $log, 'error');

                if (IS_DEV) {
                    trigger_error(json_encode($log, JSON_UNESCAPED_SLASHES), E_USER_WARNING);
                }
            }
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
            logfile('Yar::loop', $exception->getMessage(), 'error');
        }
    }

    /**
     * RPC 服务端
     * @param Object $service 实例对象
     */
    public function server($service)
    {
        try {
            $server = new \Yar_Server($service);
            $server->handle();
        } catch (Exception $exception) {
            logfile('Yar::server', $exception->getMessage(), 'error');
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
