<?php

// 重置密钥
$filename = 'config/dev/app.php';
$content = file_get_contents($filename);
$secretKey = hash_pbkdf2 ('sha256', uniqid(), '5f33ef38caf88', 1e3);
$content = str_replace('8f037a11f3aa039e1cd48bfbc70ec893', $secretKey, $content);
file_put_contents($filename, $content);

// 重置运维后台密码
$filename = 'app/Routes/OPS/index.php';
$content = file_get_contents($filename);
$content = str_replace('ops.sparrow', substr(uniqid (), -6), $content);
file_put_contents($filename, $content);

// 重置白名单 Cookie
$filename = 'config/dev/whitelist.php';
$content = file_get_contents($filename);
$cookie = hash_pbkdf2 ('sha256', uniqid(), '5f34e2f3b697e', 1e3);
$content = str_replace('e1c07665849e18b27f7a6d3e86894e8c657b628f5baccf637afc3d0a4a965a02', $cookie, $content);
file_put_contents($filename, $content);