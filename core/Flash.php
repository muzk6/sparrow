<?php


namespace Core;

/**
 * 闪存，一次性缓存
 * @package Core
 */
class Flash
{
    /**
     * @var string 缓存键前缀
     */
    protected $prefix;

    /**
     * Flash constructor.
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->prefix = $config['prefix'];
    }

    /**
     * 设置
     * @param string $key
     * @param mixed $value
     * @return mixed
     */
    public function set(string $key, $value)
    {
        return $_SESSION[$this->prefix . $key] = $value;
    }

    /**
     * 是否有值
     * @param string $key
     * @return bool true: 存在且非 null
     */
    public function has(string $key)
    {
        return isset($_SESSION[$this->prefix . $key]);
    }

    /**
     * 是否存在
     * @param string $key
     * @return bool true: 存在，即使值为 null
     */
    public function exists(string $key)
    {
        return array_key_exists($this->prefix . $key, $_SESSION);
    }

    /**
     * 获取并删除
     * @param string $key
     * @return null|mixed
     */
    public function get(string $key)
    {
        if (!$this->has($key)) {
            return null;
        }

        $value = $_SESSION[$this->prefix . $key];
        unset($_SESSION[$this->prefix . $key]);

        return $value;
    }

    /**
     * 删除
     * @param string $key
     * @return true
     */
    public function del(string $key)
    {
        if (!$this->has($key)) {
            return false;
        }

        unset($_SESSION[$this->prefix . $key]);
        return true;
    }
}
