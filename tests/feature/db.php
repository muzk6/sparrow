<?php

/**
 * 数据库操作用例
 */

require_once __DIR__ . '/../../init.php';

$sqlAll = "select * from test order by id desc limit 2";
$sqlOne = "select * from test where id=:id";

/**
 * 默认分区
 */
$ds['all'] = db()->getAll($sqlAll);
$ds['insert'] = db()->query("insert into test(name, `order`) values(?, ?)", ['tom_04', 3]);
$ds['one'] = db()->getOne($sqlOne, ['id' => $ds['insert']]);
$ds['update'] = db()->query('update test set `order`=`order`+1 where id=:id', ['id' => $ds['insert']]);
$ds['one2'] = db()->getOne($sqlOne, ['id' => $ds['insert']]);
$ds['delete'] = db()->query('delete from test where id=:id', ['id' => $ds['insert']]);
$ds['all2'] = db()->getAll($sqlAll);
$ds['one3'] = db()->getOne($sqlOne, ['id' => -1]);
// value 使用原生 sql 时，应放在数组里 e.g. 'order' => ['UNIX_TIMESTAMP()']
$ds['insert_kv'] = db()->insert(['name' => 'kv', 'order' => ['UNIX_TIMESTAMP()']], 'test', true);
$ds['one4'] = db()->getOne($sqlOne, ['id' => $ds['insert_kv']]);
$ds['update_kv'] = db()->update(['name' => 'update_kv', 'order' => 5], ['id' => $ds['insert_kv']], 'test');

// value 使用原生 sql 时，应放在数组里 e.g. 'order' => ['`order`+1']
// update(), delete() 中的 $where 都支持使用参数绑定方式 e.g. ['id=?', [$ds['insert_kv']]]
$ds['update_kv2'] = db()->update(['name' => 'update_kv', 'order' => ['`order`+1']], ['id=?', [$ds['insert_kv']]], 'test');
$ds['one5'] = db()->getOne($sqlOne, ['id' => $ds['insert_kv']]);
$ds['delete_kv'] = db()->delete(['id' => $ds['insert_kv']], 'test');
var_dump($ds);

/**
 * 默认分区事务
 */
$transaction = db()->beginTransaction();
$ds2['trans_id'] = $transaction->query('insert into test(name, `order`) values (?,?)', ['trans', 99]);
$ds2['trans'] = $transaction->getOne($sqlOne, ['id' => $ds2['trans_id']]);
$transaction->rollBack();
$ds2['trans2'] = $transaction->getOne($sqlOne, ['id' => $ds2['trans_id']]);

$transaction = db()->beginTransaction();
// 插入多条记录
$ds2['trans_insert_kv_many'] = $transaction->insert([['name' => 'kv1', 'order' => 5], ['name' => 'kv2', 'order' => 6]], 'test', true);
$transaction->commit();
$ds2['trans3'] = $transaction->getAll('select * from test order by id desc limit 2');
$ids = array_column($ds2['trans3'], 'id');
$ds2['trans_delete'] = $transaction->delete(['id IN(?,?)', $ids], 'test');
$ds2['trans4'] = $transaction->getAll('select * from test where id IN(?,?)', $ids);
var_dump($ds2);

/**
 * 分表
 */
$sharding = db()->shard('test', 1010);
var_dump($sharding->table);
$sqlSharding = "select * from {$sharding->table} where id=:id";
$ds3['sharding'] = $sharding->getOne($sqlSharding, ['id' => 122]);
$ds3['sharding_insert_ky_many'] = $sharding->insert([['name' => 'Hello', 'order' => ['UNIX_TIMESTAMP()']], ['name' => 'Sparrow', 'order' => 10]]);
$ds3['sharding2'] = $sharding->getAll($sqlAll);
$ids = array_column($ds3['sharding2'], 'id');
$ds3['sharding_update'] = $sharding->update(['order' => 1], ['id IN(?,?)', $ids]);
$ds3['sharding3'] = $sharding->getAll("select * from {$sharding->table} where id IN(?,?)", $ids);
$ds3['sharding_delete'] = $sharding->delete(['id IN(?,?)', $ids]);
$ds3['sharding4'] = $sharding->getAll("select * from {$sharding->table} where id IN(?,?)", $ids);
var_dump($ds3);

/**
 * 分表事务
 */
$shardingTransaction = $sharding->beginTransaction();
$ds4['sharding_trans'] = $shardingTransaction->getOne("select * from {$sharding->table} order by id desc");
$ds4['sharding_update'] = $shardingTransaction->query("update {$sharding->table} set `order` = `order` + 1 where id=:id", ['id' => $ds4['sharding_trans']['id']]);
$ds4['sharding_trans2'] = $shardingTransaction->getOne("select * from {$sharding->table} where id=:id", ['id' => $ds4['sharding_trans']['id']]);
$shardingTransaction->rollBack();
$ds4['sharding_trans3'] = $shardingTransaction->getOne("select * from {$sharding->table} where id=:id", ['id' => $ds4['sharding_trans']['id']]);
var_dump($ds4);
