<?php


namespace Core;

/**
 * 登录信息
 * @package Core
 */
class AppAuth
{
    protected $prefix = 'AUTH:';

    /**
     * 登录
     * @param int|string $userId 用户ID
     */
    public function login(string $userId)
    {
        $_SESSION[$this->prefix . 'user_id'] = $userId;
    }

    /**
     * 登出
     */
    public function logout()
    {
        unset($_SESSION[$this->prefix . 'user_id']);
    }

    /**
     * 用户ID
     * @return int
     */
    public function userId()
    {
        return $_SESSION[$this->prefix . 'user_id'] ?? 0;
    }

    /**
     * 是否已登录
     * @return bool
     */
    public function isLogin()
    {
        return !empty($_SESSION[$this->prefix . 'user_id']);
    }
}