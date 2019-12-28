<?php

/**
 * php-cgi 测试入口
 * http://{HOST}/test.php
 */

use Core\Whitelist;

require_once dirname(__DIR__) . '/init.php';

app(Whitelist::class)->checkSafeIpOrExit();

//todo..
// 默认分区
$sqlAll = "select * from test order by id desc limit 2";
$sqlOne = "select * from test where id=:id";
$ds['all'] = db()->selectAll($sqlAll, []);
$ds['insert'] = db()->insert("insert into test(name, `order`) values(?, ?)", ['tom_04', 3]);
$ds['one'] = db()->selectOne($sqlOne, ['id' => $ds['insert']]);
$ds['update'] = db()->update('update test set `order`=`order`+1 where id=:id', ['id' => $ds['insert']]);
$ds['one2'] = db()->selectOne($sqlOne, ['id' => $ds['insert']]);
$ds['delete'] = db()->delete('delete from test where id=:id', ['id' => $ds['insert']]);
$ds['all2'] = db()->selectAll($sqlAll);
$ds['one3'] = db()->selectOne('select * from test where id=:id', ['id' => -1]);
var_dump($ds);
db()->close();

// 默认分区事务
$transaction = db()->beginTransaction();
$ds2['faker_id'] = $transaction->insert('insert into test(name, `order`) values (?,?)', ['faker', 99]);
$ds2['faker'] = $transaction->selectOne($sqlOne, ['id' => $ds2['faker_id']]);
$transaction->rollBack();
$ds2['faker2'] = $transaction->selectOne($sqlOne, ['id' => $ds2['faker_id']]);
var_dump($ds2);
db()->close();

// 扩展分区
$sharding = db()->shard('test', 1010);
var_dump($sharding->table);
$sqlSharding = "select * from {$sharding->table} where id=:id";
$ds3['sharding'] = $sharding->selectOne($sqlSharding, ['id' => 122]);
var_dump($ds3);
db()->close();

// 扩展分区事务
$shardingTransaction = $sharding->beginTransaction();
$ds4['sharding_faker'] = $shardingTransaction->selectOne("select * from {$sharding->table} order by id desc");
$ds4['sharding_update'] = $shardingTransaction->update("update {$sharding->table} set `order` = `order` + 1 where id=:id", ['id' => $ds4['sharding_faker']['id']]);
$ds4['sharding_faker2'] = $shardingTransaction->selectOne("select * from {$sharding->table} where id=:id", ['id' => $ds4['sharding_faker']['id']]);
$shardingTransaction->rollBack();
$ds4['sharding_faker3'] = $shardingTransaction->selectOne("select * from {$sharding->table} where id=:id", ['id' => $ds4['sharding_faker']['id']]);
var_dump($ds4);
