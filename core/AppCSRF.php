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
     * 刷新令牌的过期时间
     * @return int|false 0表示不过期
     */
    public function refresh()
    {
        if (empty($_SESSION['csrf_token'])) {
            return false;
        }

        if (!$this->expire) {
            return 0;
        }

        return $_SESSION['csrf_token']['expire'] = TIME + $this->expire;
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
                'expire' => $this->expire ? TIME + $this->expire : 0,
            ];
        } else {
            // 每次获取令牌时都刷新过期时间
            $this->refresh();

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
        if (isset($_SERVER['HTTP_X_CSRF_TOKEN'])) {
            $token = $_SERVER['HTTP_X_CSRF_TOKEN'];
        } elseif (IS_POST) {
            $token = $_POST['_token'] ?? '';
        } elseif (IS_GET) {
            $token = $_GET['_token'] ?? '';
        } else {
            $token = $_REQUEST['_token'] ?? '';
        }

        if (!$token) {
            panic(10001001);
        }

        if (empty($_SESSION['csrf_token'])) {
            panic(10001002);
        }

        if ($_SESSION['csrf_token']['expire'] && (TIME > $_SESSION['csrf_token']['expire'])) {
            panic(10001002);
        }

        if ($token != $_SESSION['csrf_token']['token']) {
            panic(10001001);
        }

        return true;
    }

}
