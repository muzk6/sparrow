<?php

/**
 * 数据库分表逻辑
 * @param string $table 原始表名
 * @param string $index 分表依据
 * @return array
 */
return function (string $table, string $index) {
    $sharding = [
        'section' => '', // 对应 config/.../database.php sections 里的分区名，为空时自动切换为 default
        'dbname' => '', // 为空时取配置文件里的 dbname
        'table' => $table,
    ];

    switch ($table) {
        case 'test':
            // 例如下面注释的代码，传入的 $index 是数字 1234，以后两位为分表逻辑分100张表，计算出的分表名为: test_34
            // db()->shard('test', 1234); 为分表对象，详情查看 tests/feature/db.php
//            $suffix = str_pad(substr($index, -2), 2, '0', STR_PAD_LEFT);
//            $sharding['table'] = "{$table}_{$suffix}";

            $sharding['section'] = 'default';
            $sharding['dbname'] = 'test';
            $sharding['table'] = "test";
            break;
    }

    return $sharding;
};
