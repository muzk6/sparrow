<?php

/**
 * OPS 运维与开发
 */

require_once dirname(__DIR__) . '/../init.php';
app(\Core\Router::class)->dispatch('ops');
