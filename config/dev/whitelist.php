<?php

/**
 * 白名单，开发环境跳过
 * 建议有固定公网 IP 的用 IP 白名单, 没有则用 Cookie
 */

return [
    'ip' => [ // 支持网络号位数格式，例如 0.0.0.0/0 表示所有 IP
    ],
    'cookie' => [ // 请求时带上白名单 Cookie. 判断逻辑为 isset($_COOKIE[...])
        'e1c07665849e18b27f7a6d3e86894e8c657b628f5baccf637afc3d0a4a965a02',
    ],
    'user_id' => [
    ],
];
