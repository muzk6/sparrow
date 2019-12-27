<?php

/**
 * php-cgi 测试入口
 * http://{HOST}/test.php
 */

use Core\Whitelist;

require_once dirname(__DIR__) . '/init.php';

app(Whitelist::class)->checkSafeIpOrExit();

//todo..
$conn = db()->beginTransaction();
exit;
$sqlAll = "select * from test";
$sqlOne = "select * from test where id = :id";

$ds['all1'] = db()->getAll($sqlAll, [], false, 'sec0');
$ds['insert'] = $id = db()->query("insert into test(name, `order`) values(?, ?)", ['tom_04', 3], 'sec0');
$ds['one1'] = db()->getOne($sqlOne, ['id' => $id]);
$ds['update'] = db()->query('update test set name = :name where id = :id', ['name' => 'tom_44', 'id' => $id]);
$ds['one2'] = db()->getOne($sqlOne, ['id' => $id]);
$ds['delete'] = db()->query('delete from test where id = :id', ['id' => $id], 'sec0');
db()->close();
$ds['all2'] = db()->getAll($sqlAll);

$update = [
    'name' => 'tom_042',
    'order' => 3,
    'id' => 196
];
$sql = "update test set name=:name, `order`=:order where id=:id";
db()->query($sql, $update);

$ds['one3'] = db()->getOne('select * from test where id = :id', ['id' => 196]);

var_dump($ds);
exit;

//todo transaction
