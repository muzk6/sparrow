<?php

/**
 * 数据库
 */

return [
    'log' => true, // 日志位于 data/log/__sql_<ymd>.log

    // 默认区
    'user' => 'dev',
    'passwd' => 'itH*@$xv@Y49PjDY',
    'dbname' => 'test',
    'charset' => 'utf8mb4',
    'hosts' => [
        'master' => ['host' => 'localhost', 'port' => 3306],
        'slaves' => [
        ]
    ],

    // 扩展分区 - 垂直水平分库
    'sections' => [
        'sec0' => [
            'user' => 'dev',
            'passwd' => 'itH*@$xv@Y49PjDY',
            'dbname' => 'test',
            'hosts' => [
                'master' => ['host' => 'localhost', 'port' => 3306],
                'slaves' => [
                ]
            ]
        ],
    ]
];
