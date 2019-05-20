<?php


namespace Core;

/**
 * 闪存
 * @package Core
 */
class AppFlash
{
    protected $prefix = 'FLASH:';

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
     * 是否存在
     * @param string $key
     * @return bool
     */
    public function has(string $key)
    {
        return isset($_SESSION[$this->prefix . $key]);
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
