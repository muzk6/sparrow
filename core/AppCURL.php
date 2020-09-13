<?php


namespace Core;


/**
 * CURL 简单封装
 * @package Core
 */
class AppCURL
{
    protected $ch;

    public function __construct()
    {
        $this->ch = curl_init();
    }

    public function __destruct()
    {
        curl_close($this->ch);
    }

    /**
     * POST 请求
     * @param string|array $url
     * <p>string: 'http://sparrow.com/demo' 一般用于固定 url 的场景</p>
     * <p>array: ['rpc.sparrow', '/demo'] 即读取配置 domain.php 里的域名再拼接上 /demo 一般用于不同环境不同 url 的场景</p>
     * @param array $data POST 参数
     * @param array $headers 请求头
     * @param int $connectTimeout 请求超时(秒)
     * @return array|string|null
     */
    public function post($url, array $data = [], array $headers = [], int $connectTimeout = 3)
    {
        if (is_array($url)) {
            $url = url($url);
        }

        curl_reset($this->ch);
        curl_setopt_array($this->ch, [
            CURLOPT_URL => $url,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_POST => true,
            CURLOPT_CONNECTTIMEOUT => $connectTimeout,
            CURLOPT_POSTFIELDS => http_build_query($data),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
        ]);

        if ($headers) {
            curl_setopt($this->ch, CURLOPT_HTTPHEADER, $headers);
        }

        $out = curl_exec($this->ch);

        $info = curl_getinfo($this->ch);
        if ($info['http_code'] != 200) {
            trigger_error(json_encode($info, JSON_UNESCAPED_SLASHES), E_USER_WARNING);
            return null;
        }

        return json_decode($out, true) ?: $out;
    }

    /**
     * GET 请求
     * @param string|array $url
     * <p>string: 'http://sparrow.com/demo' 一般用于固定 url 的场景</p>
     * <p>array: ['rpc.sparrow', '/demo'] 即读取配置 domain.php 里的域名再拼接上 /demo 一般用于不同环境不同 url 的场景</p>
     * @param array $data querystring 参数
     * @param array $headers 请求头
     * @param int $connectTimeout 请求超时(秒)
     * @return bool|string|null
     */
    public function get($url, array $data = [], array $headers = [], int $connectTimeout = 3)
    {
        if (is_array($url)) {
            $url = url($url);
        }

        if ($data) {
            $url .= (strpos($url, '?') !== false ? '&' : '?') . http_build_query($data);
        }

        curl_reset($this->ch);
        curl_setopt_array($this->ch, [
            CURLOPT_URL => $url,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_CONNECTTIMEOUT => $connectTimeout,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
        ]);

        if ($headers) {
            curl_setopt($this->ch, CURLOPT_HTTPHEADER, $headers);
        }

        $out = curl_exec($this->ch);

        $info = curl_getinfo($this->ch);
        if ($info['http_code'] != 200) {
            trigger_error(json_encode($info, JSON_UNESCAPED_SLASHES), E_USER_WARNING);
            return null;
        }

        return json_decode($out, true) ?: $out;
    }

}