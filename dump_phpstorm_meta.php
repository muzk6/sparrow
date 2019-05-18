<?php

/**
 * 导出
 */

require __DIR__ . '/init.php';

$baseMeta = [
    'app.redis' => 'Redis',
    'app.queue' => 'Core\AppQueue',
    'app.yar' => 'Core\AppYar',
    'app.mail' => 'Core\AppMail',
    'app.es' => 'Elasticsearch\Client',
];

function main($baseMeta)
{
    $content = <<<EOT
<?php

// 参考：https://confluence.jetbrains.com/display/PhpStorm/PhpStorm+Advanced+Metadata
namespace PHPSTORM_META {
    override(
        \app(),
        map(
            [
{meta}
            ]
        )
    );

}

EOT;

    $blank = str_repeat(' ', 16);
    $meta = '';

    foreach ($baseMeta as $k => $v) {
        $meta .= sprintf("%s'%s' => \%s::class,\n", $blank, $k, $v);
    }

    $container = Core\AppContainer::init();
    foreach ($container->keys() as $key) {
        try {
            $className = get_class($container[$key]);
            if ($className) {
                $meta .= sprintf("%s'%s' => \%s::class,\n", $blank, $key, $className);
            }
        } catch (Exception $exception) {
        }
    }

    $meta = rtrim($meta);
    $content = str_replace('{meta}', $meta, $content);

    $filename = '.phpstorm.meta.php';
    $rs = file_put_contents(__DIR__ . '/' . $filename, $content);
    if (!$rs) {
        echo 'File error: ', $filename, ' write failed', PHP_EOL;
        exit(1);
    }

    echo 'Success: ' . $filename, PHP_EOL;
}

main($baseMeta);
