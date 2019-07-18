<?php


namespace Core;

/**
 * 白名单
 * @package Core
 */
class Whitelist
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
        /** @var Request $request */
        $request = app(Request::class);

        $clientIpStr = $request->getIp();
        $clientIp = ip2long($clientIpStr);

        foreach ($this->conf['ip'] as $v) {

            if (strpos($v, '/') === false) {
                if ($v == $clientIpStr) {
                    return true;
                }

            } else {
                list($safeIpStr, $subnetNum) = explode('/', $v);

                $base = ip2long('255.255.255.255');

                $mask = pow(2, 32 - intval($subnetNum)) - 1; // /24为例则 0.0.0.255(int)
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
     * 检查安全IP否则404
     */
    public function checkSafeIpOrExit()
    {
        if (!$this->isSafeIp()) {
            http_response_code(404);
            exit;
        }
    }

    /**
     * 当前用户是否在白名单内
     * @return bool
     */
    public function isSafeUserId()
    {
        /** @var Auth $auth */
        $auth = app(Auth::class);

        if (!$auth->isLogin()) {
            return false;
        }

        return in_array($auth->getUserId(), $this->conf['user_id']);
    }

    /**
     * 检查安全用户否则404
     */
    public function checkSafeUserIdOrExit()
    {
        if (!$this->isSafeUserId()) {
            http_response_code(404);
            exit;
        }
    }

}
