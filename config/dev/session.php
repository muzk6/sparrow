<?php

return [
//    'session.save_handler' => 'files',
//    'session.save_path' => PATH_DATA . '/session',
    'session.save_handler' => 'redis',
    'session.save_path' => 'tcp://localhost:6379',

    'session.gc_maxlifetime' => 1440, // session过期时间
    'session.cookie_lifetime' => 0, // cookie过期时间，0表示浏览器重启后失效
    'session.cookie_httponly' => 'On',
];