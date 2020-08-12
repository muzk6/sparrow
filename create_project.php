<?php

// 重置密钥
$filename = 'config/dev/app.php';
$content = file_get_contents($filename);
$secretKey = hash_pbkdf2 ('sha256', uniqid(), '5f33ef38caf88', 1e3);
$content = str_replace('8f037a11f3aa039e1cd48bfbc70ec893', $secretKey, $content);
file_put_contents($filename, $content);

// 重置运维后台密码
$filename = 'app/Controllers/OPS/IndexController.php';
$content = file_get_contents($filename);
$content = str_replace('ops.sparrow', substr(uniqid (), -6), $content);
file_put_contents($filename, $content);