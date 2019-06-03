<?php


namespace Core;

/**
 * 加密解密
 * @package Core
 */
class Crypto
{
    protected $secretKey;

    public function __construct(string $secretKey)
    {
        $this->secretKey = $secretKey;
    }

    /**
     * 加密
     * @param string $str 明文
     * @param int $expiry 过期秒数，0表示不过期
     * @param int $safeLength 随机密钥长度 取值 0-32
     * @return string
     */
    public function encrypt(string $str, $expiry = 0, $safeLength = 5)
    {
        return $this->crypt($str, $this->secretKey, 'ENCODE', $expiry, $safeLength);
    }

    /**
     * 解密
     * @param string $str 密文
     * @param int $safeLength 随机密钥长度 取值 0-32
     * @return string
     */
    public function decrypt(string $str, $safeLength = 5)
    {
        return $this->crypt($str, $this->secretKey, 'DECODE', 0, $safeLength);
    }

    /**
     * 加密、解密
     * <p>加入随机密钥，可以令密文无任何规律，即便是原文和密钥完全相同，加密结果也会每次不同，增大破解难度</p>
     * <p>取值越大，密文变动规律越大，密文变化 = 16 的 $ckeyLength 次方</p>
     * <p>当此值为 0 时，则不产生随机密钥</p>
     * @param string $str 待处理的字符串
     * @param string $key 密钥
     * @param string $operation [DECODE、ENCODE] 操作
     * @param int $expiry 过期秒数，0表示不过期
     * @param int $safeLength 随机密钥长度 取值 0-32
     *
     * @return string
     */
    protected function crypt($str, $key, $operation = 'DECODE', $expiry = 0, $safeLength = 5)
    {
        $key = md5($key);
        $keya = md5(substr($key, 0, 16));
        $keyb = md5(substr($key, 16, 16));
        $keyc = $safeLength ? ($operation == 'DECODE' ? substr($str, 0, $safeLength) : substr(md5(microtime()), -$safeLength)) : '';

        $cryptkey = $keya . md5($keya . $keyc);
        $keyLength = strlen($cryptkey);

        $str = $operation == 'DECODE' ? base64_decode(substr($str, $safeLength)) : sprintf('%010d', $expiry ? $expiry + time() : 0) . substr(md5($str . $keyb), 0, 16) . $str;
        $strLength = strlen($str);

        $result = '';
        $box = range(0, 255);
        $rndkey = [];

        for ($i = 0; $i <= 255; $i++)
        {
            $rndkey[$i] = ord($cryptkey[$i % $keyLength]);
        }

        for ($j = $i = 0; $i < 256; $i++)
        {
            $j = ($j + $box[$i] + $rndkey[$i]) % 256;
            $tmp = $box[$i];
            $box[$i] = $box[$j];
            $box[$j] = $tmp;
        }

        for ($a = $j = $i = 0; $i < $strLength; $i++)
        {
            $a = ($a + 1) % 256;
            $j = ($j + $box[$a]) % 256;
            $tmp = $box[$a];
            $box[$a] = $box[$j];
            $box[$j] = $tmp;
            $result .= chr(ord($str[$i]) ^ ($box[($box[$a] + $box[$j]) % 256]));
        }

        if ($operation == 'DECODE')
        {
            if ((substr($result, 0, 10) == 0 || substr($result, 0, 10) - time() > 0) && substr($result, 10, 16) == substr(md5(substr($result, 26) . $keyb), 0, 16))
            {
                return substr($result, 26);
            }
            else
            {
                return '';
            }
        }
        else
        {
            return $keyc . str_replace('=', '', base64_encode($result));
        }
    }

}
