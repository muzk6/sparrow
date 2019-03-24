<?php

namespace App\Core;

use Closure;

/**
 * <p>
 * $context = [ 'middleware' => ..., 'uri' => ..., 'controller' => ..., 'action' => ...]
 * </p>
 * @package Core
 */
class AppMiddleware extends \Core\AppMiddleware
{
    /**
     * @inheritdoc
     */
    public function auth(Closure $next, array $context)
    {
        parent::auth($next, $context);
    }

}
