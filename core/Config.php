<?php


namespace Core;

/**
 * 配置
 * @package Core
 */
class Config
{
    /**
     * @var array 配置文件集体
     */
    protected $config = [];

    /**
     * 从配置文件读取配置
     * <p>优先从当前环境目录搜索配置文件</p>
     * @param string $key 配置文件.配置项0.配置项1
     * @return mixed
     */
    public function get(string $key)
    {
        $keys = explode('.', $key);
        $filename = array_shift($keys);

        $config = &$this->config[$filename];
        if (!isset($config)) {
            if (is_file($path = PATH_CONFIG_ENV . "/{$filename}.php")) {
                $config = include($path);
            } else if (is_file($path = PATH_CONFIG . "/{$filename}.php")) {
                $config = include($path);
            } else {
                trigger_error("{$filename}.php 配置文件不存在");
                return '';
            }
        }

        $value = $config;
        foreach ($keys as $item) {
            if (!isset($value[$item])) {
                trigger_error("配置项 {$key} 不存在");
                return '';
            }

            $value = $value[$item];
        }

        return $value;
    }

    /**
     * 设置、覆盖 runtime 配置
     * @param string $key
     * @param $value
     * @return bool
     */
    public function set(string $key, $value): bool
    {
        $keys = explode('.', $key);
        $filename = array_shift($keys);

        $config = &$this->config[$filename];
        if (!isset($config)) {
            $config = [];
        }

        $ref = &$config;
        foreach ($keys as $item) {
            if (!isset($ref[$item])) {
                $ref[$item] = [];
            }

            $ref = &$ref[$item];
        }

        $ref = $value;
        return true;
    }

}
