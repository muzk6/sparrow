<?php

namespace Core;

/**
 * CSRF
 * @package Core
 */
class AppCSRF
{
    /**
     * 令牌<br>
     * 会话初始化时才更新 token
     * @return string
     */
    public function token()
    {
        if (empty($_SESSION['csrf_token'])) {
            $token = sha1(uniqid(mt_rand(1000, 9999) . session_id()));
            $_SESSION['csrf_token'] = $token;
        } else {
            $token = $_SESSION['csrf_token'];
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
        if (empty($token)) {
            throw new AppException(10001002);
        }

        if (isset($_SESSION['csrf_token']) && $_SESSION['csrf_token'] == $token) {
            return true;
        }

        throw new AppException(10001002);
    }
}