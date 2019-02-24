<?php

namespace Core;

/**
 * CSRF
 * @package Core
 */
class AppCSRF
{
    /**
     * @var string 密钥
     */
    protected $secretKey;

    /**
     * @var int 过期时间(秒)
     */
    protected $expire;

    /**
     * @param array $conf 配置
     */
    public function __construct(array $conf)
    {
        $this->secretKey = $conf['secret_key'];
        $this->expire = $conf['expire'];
    }

    /**
     * 令牌<br>
     * 会话初始化时才更新 token
     * @return string
     */
    public function token()
    {
        if (empty($_SESSION['csrf_token'])) {
            $token = hash_hmac('sha256', session_id(), $this->secretKey);

            $_SESSION['csrf_token'] = [
                'token' => $token,
                'expire' => TIME + $this->expire,
            ];
        } else {
            if (TIME > $_SESSION['csrf_token']['expire']) {
                unset($_SESSION['csrf_token']);

                return $this->token();
            }

            $token = $_SESSION['csrf_token']['token'];
        }

        return $token;
    }

    /**
     * 带有 token 的表单域 html 元素
     * @return string
     */
    public function field()
    {
        $token = $this->token();
        return '<input type="hidden" name="_token" value="' . $token . '">';
    }

    /**
     * token 校验
     * @return true
     * @throws AppException
     */
    public function check()
    {
        $token = $_POST['_token'] ?? '';

        if (!$token) {
            throw new AppException(10001001);
        }

        if (empty($_SESSION['csrf_token'])) {
            throw new AppException(10001002);
        }

        if (TIME > $_SESSION['csrf_token']['expire']) {
            throw new AppException(10001002);
        }

        if ($token != $_SESSION['csrf_token']['token']) {
            throw new AppException(10001001);
        }

        return true;
    }
}