<?php

/**
 * 数据库操作用例
 */

require_once __DIR__ . '/../../init.php';

/**
 * INSERT
 */
$ds['insert_sql'] = db()->query("insert into test(name, `order`) values(?, ?)", ['sparrow_1', 1]);
// insert KV, insert ignore
$ds['insert_kv'] = db()->insert(['name' => 'sparrow_2', 'order' => 2], 'test', true);
// insert ignore
$ds['insert_kv2'] = db()->insert(['name' => ['?', 'sparrow_3'], 'order' => ['UNIX_TIMESTAMP()']], 'test', true);
// 批量插入
$ds['insert_kv_many'] = db()->insert([['name' => 'sparrow_m1', 'order' => 1], ['name' => 'sparrow_m2', 'order' => 2]], 'test');

/**
 * SELECT
 */
$ds['select_sql'] = db()->getOne('select * from test where id=:id', ['id' => $ds['insert_sql']]);
$ds['select_sql2'] = db()->getAll('select * from test where id in(?,?)', [$ds['insert_kv'], $ds['insert_kv']]);
// WHERE 参数绑定，['col0=?', 1]
$ds['select_one'] = db()->selectOne('*', ['id=?', $ds['insert_sql']], 'test');
// WHERE 参数绑定，['col0=?', [1]]; 结果同上
$ds['select_one2'] = db()->selectOne('*', ['id=?', [$ds['insert_sql']]], 'test');
// WHERE and KV
$ds['select_one3'] = db()->selectOne('*', ['name' => 'sparrow_2', 'order' => 2], 'test');
// 固定字符串条件
$ds['select_one4'] = db()->selectOne('*', '`order`=2', 'test');
// 无条件
$ds['select_all3'] = db()->selectAll('*', '', 'test', 'id desc', [5]);

// 查询业务示例
$name = 'sparrow';
$order = 2;
$where = [];

if ($name) {
    $where[] = ['and name like ?', "%{$name}%"]; // 或者带上key $where['name'] 也可以，可用于覆盖同 key 的条件
}

if ($order) {
    $where['order'] = $order;
}
// 直接使用 WHERE 组合条件
$ds['select_where'] = db()->selectAll('*', $where, 'test');

// 纯 SQL 时，需先 parseWhere() 转换成 holder, binds 形式
$hBinds = db()->parseWhere($where);
$ds['select_where2'] = db()->getAll("select * from test {$hBinds[0]}", $hBinds[1]);

/**
 * UPDATE
 */
$ds['update_sql'] = db()->query('update test set `order`=`order`+1 where id=:id', ['id' => $ds['insert_sql']]);
// WHERE 参数绑定
$ds['update_kv'] = db()->update(['name' => 'sparrow_u1'], ['id=?', $ds['insert_sql']], 'test');
// SET 参数绑定；WHERE KV
$ds['update_kv2'] = db()->update(['order' => ['`order`+?', 1], 'name' => ['?', 'update_kv']], ['id' => $ds['insert_sql']], 'test');
$ds['update_kv3'] = db()->update(['order' => ['UNIX_TIMESTAMP()']], ['id' => $ds['insert_kv']], 'test');
$ds['select_all4'] = db()->selectAll('*', ['id in(?,?)', $ds['insert_sql'], $ds['insert_kv']], 'test');

/**
 * DELETE
 */
$ds['delete_sql'] = db()->query('delete from test where id=?', [$ds['insert_sql']]);
$ds['delete_kv'] = db()->delete(['id' => $ds['insert_kv']], 'test');
$ids = implode(',', array_column($ds['select_all3'], 'id'));
// 不参数绑定
$ds['delete_all'] = db()->delete(["id in({$ids})"], 'test');
$ds['select_all5'] = db()->selectAll('*', ["id in({$ids})"], 'test');
var_dump($ds);

/**
 * 事务
 */
$transaction = db()->beginTransaction();
$ds2['trans_insert'] = $transaction->insert(['name' => 'trans', 'order' => 99], 'test');
$ds2['trans_select'] = $transaction->selectOne('*', ['id=?', $ds2['trans_insert']], 'test');
$ds2['trans_commit'] = $transaction->commit();
$ds2['trans_delete'] = $transaction->delete(['id=?', $ds2['trans_insert']], 'test');
$ds2['trans_select2'] = $transaction->selectOne('*', ['id=?', $ds2['trans_insert']], 'test');
var_dump($ds2);

/**
 * 分表
 * 自动切换 table, section; 不需显式指定
 */
$sharding = db()->shard('test', 123);
$ds3['sharding_insert'] = $sharding->insert([['name' => 'Hello', 'order' => ['UNIX_TIMESTAMP()']], ['name' => 'Sparrow', 'order' => 10]]);
// 使用 $sharding->selectAll(), 不用指定分表 table, section; 其它非纯 SQL 方法同理
$ds3['sharding_select'] = $sharding->selectAll('*', '', '', 'id DESC', [2]);
$ids = array_column($ds3['sharding_select'], 'id');
$ds3['sharding_update'] = $sharding->update(['order' => 1], ['id IN(?,?)', $ids]);
// 使用 $sharding->getAll(), 只需指定分表 table
$ds3['sharding_select2'] = $sharding->getAll("select * from {$sharding->table} where id IN(?,?)", $ids);
$ds3['sharding_delete'] = $sharding->delete(['id IN(?,?)', $ids]);
// 也可以使用 db()->getAll(), 但需要指定分表 table, section
$ds3['sharding_select3'] = db()->getAll("select * from {$sharding->table} where id IN(?,?)", $ids, false, $sharding->section);
var_dump($ds3);

/**
 * 分表事务
 * 自动切换 section, table; 不需显式指定
 */
$shardingTransaction = $sharding->beginTransaction();
// 分表事务对象，非纯 SQL 方法同理可以不指定 table, section
$ds4['sharding_trans_insert'] = $shardingTransaction->insert(['name' => 'sharding_trans']);
$ds4['sharding_trans_select'] = $shardingTransaction->selectOne('*', '', '', 'id DESC');
$ds4['sharding_trans_update'] = $shardingTransaction->update(['order' => ['`order`+1']], ['id' => $ds4['sharding_trans_select']['id']]);
$ds4['sharding_trans_select2'] = $shardingTransaction->selectOne('*', ['id' => $ds4['sharding_trans_select']['id']]);
$ds4['sharding_trans_rollback'] = $shardingTransaction->rollBack();
$ds4['sharding_trans_select3'] = $shardingTransaction->selectOne('*', ['id' => $ds4['sharding_trans_select']['id']]);
var_dump($ds4);
