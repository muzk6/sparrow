<?php


namespace Core;

/**
 * 登录信息
 * @package Core
 */
class Auth
{
    /**
     * @var string 缓存键前缀
     */
    protected $prefix;

    /**
     * AppAuth constructor.
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->prefix = $config['prefix'];
    }

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
     * @return int|string
     */
    public function getUserId()
    {
        if (isset($_SESSION[$this->prefix . 'user_id'])) {
            if (is_numeric($_SESSION[$this->prefix . 'user_id'])) {
                return intval($_SESSION[$this->prefix . 'user_id']);
            } else {
                return $_SESSION[$this->prefix . 'user_id'];
            }
        } else {
            return 0;
        }
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
