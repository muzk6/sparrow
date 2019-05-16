<?php

// 参考：https://confluence.jetbrains.com/display/PhpStorm/PhpStorm+Advanced+Metadata
namespace PHPSTORM_META {
    override(
        \app(),
        map(
            [
                'Core\AppAes' => \Core\AppAes::class,
                'Core\AppAuth' => \Core\AppAuth::class,
                'App\Models\DemoModel' => \App\Models\DemoModel::class,
                'App\Services\DemoService' => \App\Services\DemoService::class,
            ]
        )
    );

}
