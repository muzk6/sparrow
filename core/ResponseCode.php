<?php


namespace Core;

/**
 * 非200响应状态的处理
 * @package Core
 */
class ResponseCode
{
    /**
     * URI规则不匹配或控制器方法不存在时 404
     */
    public function status404()
    {
        http_response_code(404);
    }

}
