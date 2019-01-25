<?php

namespace Core;

/**
 * openssl 二次封装
 * @package Core
 */
class AppOpenSSL
{
    /**
     * aes 对称加密
     * @param string $plainText 明文
     * @return array
     * ['cipher' => $cipher, 'iv' => $iv, 'key' => $key, 'is_strong' => $isStrong]
     */
    function aesEncrypt(string $plainText)
    {
        $method = 'aes-256-cbc';
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($method), $isStrong);

        $key = md5(uniqid());
        $cipher = openssl_encrypt($plainText, $method, $key, 0, $iv);

        return [
            'cipher' => $cipher,
            'iv' => $iv,
            'key' => $key,
            'is_strong' => $isStrong,
        ];
    }

    /**
     * aes 对称解密
     * @param string $cipher base64格式的密文
     * @param string $iv 分组加密的初始向量
     * @param string $key 密钥
     * @return string 密文
     */
    function aesDecrypt(string $cipher, $iv, $key)
    {
        $method = 'aes-256-cbc';
        $plainText = openssl_decrypt($cipher, $method, $key, 0, $iv);

        return $plainText;
    }
}