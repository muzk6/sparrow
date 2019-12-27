<?php

/**
 * php-cgi 测试入口
 * http://{HOST}/test.php
 */

use Core\Whitelist;

require_once dirname(__DIR__) . '/init.php';

app(Whitelist::class)->checkSafeIpOrExit();

//todo..
$sqlAll = "select * from test";
$sqlOne = "select * from test where id = :id";
$ds['all1'] = db()->selectAll($sqlAll, [], false, 'sec0');
$ds['insert'] = $id = db()->insert("insert into test(name, `order`) values(?, ?)", ['tom_04', 3], 'sec0');
$ds['one1'] = db()->selectOne($sqlOne, ['id' => $id]);
$ds['update'] = db()->update('update test set name = :name where id = :id', ['name' => 'tom_44', 'id' => $id]);
$ds['one2'] = db()->selectOne($sqlOne, ['id' => $id]);
$ds['delete'] = db()->delete('delete from test where id = :id', ['id' => $id], 'sec0');
$ds['all2'] = db()->selectAll($sqlAll);
$ds['one3'] = db()->selectOne('select * from test where id = :id', ['id' => 196]);
db()->close();

$pdo = db()->beginTransaction();
$ds['faker_id'] = $pdo->insert('insert into test(name, `order`) values (?,?)', ['faker', 99]);
$ds['faker'] = $pdo->selectOne($sqlOne, ['id' => $ds['faker_id']]);
$pdo->rollBack();
$ds['faker2'] = $pdo->selectOne($sqlOne, ['id' => $ds['faker_id']]);

$table = 'test';
$sql = "select * from {$table} where id = :id";
$ds['sharding'] = db()->selectOne($sql, ['id' => 122]);

var_dump($ds);
exit;

//todo transaction
