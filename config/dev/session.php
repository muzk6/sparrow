<?php

/**
 * 会话
 */

return [
    // 文件 session
    'session.save_handler' => 'files',
    'session.save_path' => PATH_DATA . '/session',

    // redis session
//    'session.save_handler' => 'redis',
//    'session.save_path' => 'tcp://redis:6379',

    'session.gc_maxlifetime' => 1440, // session过期时间
    'session.cookie_lifetime' => 0, // cookie过期时间，0表示浏览器重启后失效
    'session.name' => 'user_session',
    'session.cookie_httponly' => 'On',
];
