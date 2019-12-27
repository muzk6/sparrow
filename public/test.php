<?php

/**
 * php-cgi 测试入口
 * http://{HOST}/test.php
 */

use Core\Whitelist;

require_once dirname(__DIR__) . '/init.php';

app(Whitelist::class)->checkSafeIpOrExit();

//todo..
//var_dump(db()->getConnection(0));exit;
//db()->getConnection()->beginTransaction();
//db()->getConnection()->beginTransaction();
//db()->getConnection()->beginTransaction();
//db()->getConnection()->commit();
//exit;
$sqlAll = "select * from test";
$sqlOne = "select * from test where id = :id";

$ds['all'] = db()->getAll($sqlAll, [], false, 'sec0');
$ds['insert'] = $id = db()->query("insert into test(name, `order`) values(?, ?)", ['tom_04', 3], 'sec0');
$ds['one'] = db()->getOne($sqlOne, ['id' => $id]);
$ds['update'] = db()->query('update test set name = :name where id = :id', ['name' => 'tom_44', 'id' => $id]);
$ds['one'] = db()->getOne($sqlOne, ['id' => $id]);
$ds['delete'] = db()->query('delete from test where id = :id', ['id' => $id], 'sec0');
db()->close();
$ds['all'] = db()->getAll($sqlAll);

var_dump($ds);
exit;

//todo transaction
