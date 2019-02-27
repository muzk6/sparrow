<?php

namespace App\Core;

use Closure;

/**
 * 控制器中间件
 * @package Core
 */
class AppMiddleware extends \Core\AppMiddleware
{
    /**
     * @inheritdoc
     */
    public function checkAuth(Closure $next, array $context)
    {
        parent::checkAuth($next, $context);
    }

}