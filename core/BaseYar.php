<?php


namespace Core;


abstract class BaseYar
{
    public function __construct()
    {
        // 避免污染 header 导致错误 `malformed response header`
        if (isset($_SERVER['HTTP_USER_AGENT']) && strpos($_SERVER['HTTP_USER_AGENT'], 'PHP Yar') !== false) {
            ini_set('display_errors', 0);
        }
    }

}
