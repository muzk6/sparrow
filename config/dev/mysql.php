<?php

/**
 * PDO - MySQL 数据库配置
 */

return [
    'log' => true, // 生产环境建议关掉；日志位于 data/log/sql_<ym>.log
    'sections' => [
        // 默认区
        'default' => [
            // 主库
            'master' => [
                'dsn' => 'mysql:host=mysql;port=3306;dbname=test;charset=utf8mb4;',
                'user' => 'root',
                'passwd' => 'xxxx',
                'options' => [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            ],
            // 从库
//            'slaves' => [
//                [
//                    'dsn' => 'mysql:host=mysql;port=3306;dbname=test;charset=utf8mb4;',
//                    'user' => 'root',
//                    'passwd' => 'xxxx',
//                    'options' => [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
//                ],
//            ],
        ],
        // 扩展分区
//        'sec0' => [
//            'master' => [
//                'dsn' => 'mysql:host=mysql;port=3306;dbname=test;charset=utf8mb4;',
//                'user' => 'root',
//                'passwd' => 'xxxx',
//                'options' => [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
//            ],
//        ],
    ],

    /**
     * 分表逻辑
     * @param string $table 表名(前缀)
     * @param string $index 分表依据
     * @return array
     */
    'sharding' => function (string $table, string $index) {
        $sharding = [
            'section' => 'default', // 分区名
            'dbname' => 'test', // 数据库名，没有库名则设置为空(例如 SQLite 场景)
            'table' => $table,
        ];

        switch ($table) {
            case 'test':
                // 例如下面注释的代码，传入的 $index 是数字 1234，如果以后两位为分表逻辑分100张表，计算出的分表名为: test_34
//                $suffix = str_pad(substr($index, -2), 2, '0', STR_PAD_LEFT);
//                $sharding['table'] = "{$table}_{$suffix}";

                $sharding['section'] = 'default';
                $sharding['dbname'] = 'test';
                $sharding['table'] = 'test';
                break;
        }

        return $sharding;
    }
];
