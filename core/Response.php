<?php


namespace Core;

/**
 * HTTP响应
 * @package Core
 */
class Response
{
    /**
     * URI规则不匹配或控制器方法不存在时 404
     */
    public function status404()
    {
        http_response_code(404);
        return null;
    }

}
