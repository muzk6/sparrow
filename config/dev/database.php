<?php

/**
 * MySQL 数据库
 */

return [
    'log' => true, // 日志位于 data/log/sql_<ym>.log
    'sections' => [
        // 默认区
        'default' => [
            'user' => 'dev',
            'passwd' => 'itH*@$xv@Y49PjDY',
            'dbname' => 'test',
            'charset' => 'utf8mb4',
            'hosts' => [
                'master' => ['host' => 'localhost', 'port' => 3306],
                'slaves' => [
                    ['host' => 'localhost', 'port' => 3306],
                ]
            ],
        ],
        // 扩展分区
        'sec0' => [
            'user' => 'dev',
            'passwd' => 'itH*@$xv@Y49PjDY',
            'dbname' => 'test',
            'hosts' => [
                'master' => ['host' => 'localhost', 'port' => 3306],
                'slaves' => [
                    ['host' => 'localhost', 'port' => 3306],
                ]
            ]
        ],
    ]
];
