<?php


namespace Core;

/**
 * XHProf
 * @package Core
 */
class XHProf
{
    protected $config;

    protected $startTime;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * 按配置自动开启
     * @return bool
     */
    public function auto()
    {
        if (empty($this->config['enable'])) {
            return false;
        }

        if (mt_rand(1, $this->config['probability']) != 1) {
            return false;
        }

        return $this->start();
    }

    /**
     * 手动开启
     * @return bool
     */
    public function start()
    {
        if (defined('TEST_ENV')) {
            return false;
        }

        if (!extension_loaded('tideways_xhprof')) {
            trigger_error('请安装扩展: tideways_xhprof', E_USER_WARNING);
            return false;
        }

        $this->startTime = microtime(true);
        tideways_xhprof_enable(TIDEWAYS_XHPROF_FLAGS_CPU | TIDEWAYS_XHPROF_FLAGS_MEMORY);

        $alias = $this;
        register_shutdown_function(function () use ($alias) {
            $alias->shutdown();
        });

        return true;
    }

    /**
     * 结束回调
     * @return bool
     */
    protected function shutdown()
    {
        $endTime = microtime(true);
        $costTime = $endTime - $this->startTime;
        if ($costTime < $this->config['min_time']) {
            return false;
        }

        $path = PATH_DATA . '/xhprof';
        if (!file_exists($path)) {
            mkdir($path, 0755, true);
        }

        if (PHP_SAPI == 'cli') {
            $cmd = basename($_SERVER['argv'][0]);
            $url = $cmd . ' ' . implode(' ', array_slice($_SERVER['argv'], 1));
        } else {
            $url = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
        }

        $name = "{$url};{$costTime}";
        $name = rtrim(str_replace(['+', '/'], ['-', '_'], base64_encode($name)), '=');

        $data = tideways_xhprof_disable();
        file_put_contents(
            sprintf('%s/%s.%s.xhprof', $path, $name, uniqid()),
            serialize($data)
        );
    }
}
