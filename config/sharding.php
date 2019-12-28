<?php

/**
 * 数据库分表逻辑
 * @param string $table 原始表名
 * @param string $index 分表依据
 * @return array
 */
return function (string $table, string $index) {
    $sharding = [
        'section' => '', // 对应 config/dev/database.php sections 里的分区名，为空时自动切换为 default
        'dbname' => '', // 为空时取配置文件里的 dbname
        'table' => $table,
    ];

    switch ($table) {
        case 'test':
//            $suffix = str_pad(substr($index, -2), 2, '0', STR_PAD_LEFT);
            $sharding['section'] = 'default';
            $sharding['dbname'] = 'test';
            $sharding['table'] = "test";
            break;
    }

    return $sharding;
};
