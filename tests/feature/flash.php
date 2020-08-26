<?php

/**
 * \Core\Flash 测试
 */

require_once __DIR__ . '/../../init.php';

flash_set('test', '');
var_dump(flash_has('test') === true);
var_dump(flash_exists('test') === true);

flash_set('test2', null);
var_dump(flash_has('test2') === false);
var_dump(flash_exists('test2') === true);