<?php

namespace Core;

/**
 * aes 加密解密
 * @package Core
 */
class Aes
{
    /**
     * 默认密钥
     * @var string
     */
    protected $secretKey;

    public function __construct($secretKey)
    {
        $this->secretKey = $secretKey;
    }

    /**
     * 加密
     * @param string $plainText 明文
     * @return array
     * ['cipher' => $cipher, 'iv' => $iv]
     */
    function encrypt(string $plainText)
    {
        $method = 'aes-128-cbc';
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($method));

        $cipher = openssl_encrypt($plainText, $method, $this->secretKey, 0, $iv);

        return [
            'cipher' => $cipher,
            'iv' => base64_encode($iv),
        ];
    }

    /**
     * 解密
     * @param string $cipher base64格式的密文
     * @param string $iv 分组加密的初始向量
     * @return string 密文
     */
    function decrypt(string $cipher, $iv)
    {
        $method = 'aes-128-cbc';
        $plainText = openssl_decrypt($cipher, $method, $this->secretKey, 0, base64_decode($iv));

        return $plainText;
    }
}
