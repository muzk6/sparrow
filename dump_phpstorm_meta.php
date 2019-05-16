<?php

/**
 * 导出
 */

require __DIR__ . '/init.php';


function main()
{
    $content = <<<EOT
<?php

// 参考：https://confluence.jetbrains.com/display/PhpStorm/PhpStorm+Advanced+Metadata
namespace PHPSTORM_META {
    override(
        \app(),
        map(
            [
{class}
            ]
        )
    );

}

EOT;

    $blank = str_repeat(' ', 16);
    $classes = '';

    $container = Core\AppContainer::init();
    foreach ($container->keys() as $key) {
        $className = get_class($container[$key]);
        if ($className) {
            $classes .= sprintf("%s'%s' => \%s::class,\n", $blank, $key, $className);
        }
    }

    $classes = rtrim($classes);
    $content = str_replace('{class}', $classes, $content);

    $filename = '.phpstorm.meta.php';
    $rs = file_put_contents(__DIR__ . '/' . $filename, $content);
    if (!$rs) {
        echo 'File error: ', $filename, ' write failed', PHP_EOL;
        exit(1);
    }

    echo 'Success: ' . $filename, PHP_EOL;
}

main();
