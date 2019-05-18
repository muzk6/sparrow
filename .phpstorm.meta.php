<?php

// 参考：https://confluence.jetbrains.com/display/PhpStorm/PhpStorm+Advanced+Metadata
namespace PHPSTORM_META {
    override(
        \app(),
        map(
            [
                'app.redis' => \Redis::class,
                'app.queue' => \Core\AppQueue::class,
                'app.yar' => \Core\AppYar::class,
                'app.mail' => \Core\AppMail::class,
                'app.es' => \Elasticsearch\Client::class,
                'app.db' => \Core\AppPDO::class,
                'app.aes' => \Core\AppAes::class,
                'app.auth' => \Core\AppAuth::class,
                'app.admin' => \Core\AppAuth::class,
                'app.whitelist' => \Core\AppWhitelist::class,
                'app.flash' => \Core\AppFlash::class,
                'app.xdebug' => \Core\AppXdebug::class,
                'app.csrf' => \Core\AppCSRF::class,
                'app.response.code' => \Core\AppResponseCode::class,
                'app.middleware' => \App\Core\AppMiddleware::class,
                '.DemoModel' => \App\Models\DemoModel::class,
                '.DemoService' => \App\Services\DemoService::class,
            ]
        )
    );

}
