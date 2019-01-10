<?php

require_once dirname(__DIR__) . '/boot/init.php';

app_consume('app_task', function ($data) {
    var_dump($data);
});