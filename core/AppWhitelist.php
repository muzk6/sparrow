<?php


namespace Core;

/**
 * 白名单
 * @package Core
 */
class AppWhitelist
{
    /**
     * @var array 配置文件，格式 core/AppWhitelist.php
     */
    protected $conf;

    public function __construct(array $conf)
    {
        $this->conf = $conf;
    }

    /**
     * 当前 IP 是否在白名单内
     * @return bool
     */
    public function isSafeIp()
    {
        $clientIpStr = client_ip();
        $clientIp = ip2long($clientIpStr);

        foreach ($this->conf['ip'] as $v) {

            if (strpos($v, '/') === false) {
                if ($v == $clientIpStr) {
                    return true;
                }

            } else {
                list($safeIpStr, $maskStr) = explode('/', $v);

                $base = ip2long('255.255.255.255');

                $mask = pow(2, 32 - intval($maskStr)) - 1; // /24为例则 0.0.0.255(int)
                $subnetMask = $mask ^ $base; // 子网掩码，/24为例 255.255.255.0(int)

                $safeIp = ip2long($safeIpStr);
                if ($safeIp == ($clientIp & $subnetMask)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * 当前用户是否在白名单内
     * @return bool
     */
    public function isSafeUserId()
    {
        if (!auth()->isLogin()) {
            return false;
        }

        return in_array(auth()->userId(), $this->conf['user_id']);
    }

}