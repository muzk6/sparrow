<?php

/**
 * OPS 运维与开发
 */

require dirname(__DIR__) . '/../init.php';
app(\Core\Router::class)->dispatch('ops');
