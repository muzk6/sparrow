<?php

/**
 * 数据库
 */

return [
    // 默认区
    'user' => 'root',
    'passwd' => '123abc',
    'dbname' => 'test',
    'hosts' => [
        'master' => ['host' => 'direwolf', 'port' => 3306],
        'slaves' => [
//            ['host' => 'direwolf', 'port' => 3306],
        ]
    ],

    // 扩展分区 - 垂直水平分库
    'sections' => [
        'sec0' => [
            'user' => 'root',
            'passwd' => '123abc',
            'dbname' => 'test',
            'hosts' => [
                'master' => ['host' => 'direwolf', 'port' => 3306],
                'slaves' => [
//                    ['host' => 'direwolf', 'port' => 3306],
                ]
            ]
        ],
    ]
];