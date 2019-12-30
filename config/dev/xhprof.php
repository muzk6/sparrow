<?php

return [
    'enable' => false,
    'probability' => 1, // 采样率(1/probability)，触发条件为: mt_rand(1, probability) == 1
    'min_time' => 0.1, // 最小耗时，单位秒
];
