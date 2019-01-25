<?php

return [
    'user' => 'root',
    'passwd' => '123abc',
    'dbname' => 'test',
    'hosts' => [
        'master' => ['host' => 'direwolf', 'port' => 3306],
        'slaves' => [
            ['host' => 'direwolf', 'port' => 3306],
        ],
    ]
];