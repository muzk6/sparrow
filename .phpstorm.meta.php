<?php

namespace PHPSTORM_META {

    use App\Models\DemoModel;
    use App\Services\DemoService;
    use Core\Aes;
    use Core\AppPDO;
    use Core\Auth;
    use Core\Config;
    use Core\Crypto;
    use Core\CSRF;
    use Core\Flash;
    use Core\Mail;
    use Core\Queue;
    use Core\Request;
    use Core\Response;
    use Core\Translator;
    use Core\Whitelist;
    use Core\Xdebug;
    use Core\XHProf;
    use Core\Yar;
    use duncan3dc\Laravel\BladeInstance;
    use Redis;

    override(
        \app(),
        map([
            // Utils
            AppPDO::class => AppPDO::class,
            Queue::class => Queue::class,
            Config::class => Config::class,
            Translator::class => Translator::class,
            BladeInstance::class => BladeInstance::class,
            Auth::class => Auth::class,
            Request::class => Request::class,
            CSRF::class => CSRF::class,
            Xdebug::class => Xdebug::class,
            Flash::class => Flash::class,
            Response::class => Response::class,
            XHProf::class => XHProf::class,
            Yar::class => Yar::class,
            Whitelist::class => Whitelist::class,
            Redis::class => Redis::class,
            Aes::class => Aes::class,
            Mail::class => Mail::class,
            Crypto::class => Crypto::class,
            \Elasticsearch\Client::class => \Elasticsearch\Client::class,
            \duncan3dc\Laravel\BladeInstance::class => \duncan3dc\Laravel\BladeInstance::class,

            // Models
            DemoModel::class => DemoModel::class,

            // Services
            DemoService::class => DemoService::class,
        ])
    );
}