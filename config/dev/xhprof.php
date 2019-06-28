<?php

return [
    'enable' => false,
    'namespace' => 'sparrow', // 命名空间，即项目名
    'probability' => 1, // 采样率(1/probability)，触发条件为: mt_rand(1, probability) == 1
    'min_time' => 0.1, // 最小耗时，单位秒
    'save_path' => '/tmp',
];
