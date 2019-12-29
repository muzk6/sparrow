<?php


namespace Core;

use duncan3dc\Laravel\BladeInstance;
use duncan3dc\Laravel\DirectivesInterface;

/**
 * 基于 Blade 封装的模板引擎
 * @package Core
 */
class Blade extends BladeInstance
{
    /**
     * @var array 需要 assign 的变量集合
     */
    protected $assignVars = [];

    public function __construct(string $path, string $cache, DirectivesInterface $directives = null)
    {
        parent::__construct($path, $cache, $directives);

        if (IS_DEV && file_exists($cache)) {
            array_map('unlink', glob($cache . '/*'));
        }
    }

    /**
     * 定义模板变量
     * @param string $name
     * @param mixed $value
     * @return Blade
     */
    public function assign(string $name, $value)
    {
        $this->assignVars[$name] = $value;

        return $this;
    }

    /**
     * 渲染视图模板
     * @param string $view 模板名
     * @param array $params 模板里的参数
     * @return string
     */
    public function view(string $view, array $params = [])
    {
        $params = array_merge($this->assignVars, $params);
        $this->assignVars = [];

        return $this->render($view, array_merge($this->assignVars, $params));
    }

}
