<?php

namespace App\Core;

/**
 * 控制器中间件
 * @package Core
 */
class AppMiddleware extends \Core\AppMiddleware
{
    /**
     * @inheritdoc
     */
    public function checkAuth()
    {
        return parent::checkAuth();
    }

}