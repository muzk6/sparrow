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
        if (defined('TEST_ENV')) {
            return false;
        }

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
        if (!extension_loaded('tideways_xhprof')) {
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

        $data = tideways_xhprof_disable();
        $path = rtrim($this->config['save_path'], '/');
        file_put_contents(
            sprintf('%s/%s.%s.xhprof', $path, uniqid(), $this->config['namespace']),
            serialize($data)
        );
    }
}
