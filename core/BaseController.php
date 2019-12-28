<?php

namespace Core;

use duncan3dc\Laravel\BladeInstance;

/**
 * 控制器基类
 * @package Core
 */
abstract class BaseController
{
    /**
     * 用户ID
     * @var int
     */
    protected $userId;

    /**
     * 是否已经登录
     * @var bool
     */
    protected $isLogin;

    private $assignVars = [];

    public function __construct(Auth $auth)
    {
        $this->userId = $auth->getUserId();
        $this->isLogin = $auth->isLogin();
    }

    /**
     * action 前置勾子
     */
    public function beforeAction()
    {
    }

    /**
     * action 后置勾子
     */
    public function afterAction()
    {
        logfile('access', ['__POST' => $_POST], 'access');
    }

    /**
     * 为视图模板分配变量
     * @param string $name
     * @param mixed $value
     */
    protected function assign(string $name, $value)
    {
        $this->assignVars[$name] = $value;
    }

    /**
     * 渲染视图模板
     * @param string $view 模板名
     * @param array $params 模板里的参数
     * @return string
     */
    protected function view(string $view, array $params = [])
    {
        $params = array_merge($this->assignVars, $params);
        $this->assignVars = [];

        return app(BladeInstance::class)->render($view, array_merge($this->assignVars, $params));
    }

    /**
     * 从 $_GET, $_POST 获取请求参数，支持 payload
     * <p>
     * 简单用例：input('age') 即 $_POST['age'] <br>
     * 高级用例：input('post.age:i', 18, function ($val) { return $val+1; }) <br>
     * 即 $_POST['age']不存在时默认为18，最终返回 intval($_GET['age'])+1
     * @param string $field [(post|get|request).]<field_name>[.(i|b|a|f|d|s)]<br>
     * 参数池默认为 $_POST<br>
     * field_name 为字段名<br>
     * 类型强转：i=int, b=bool, a=array, f=float, d=double, s=string(默认)
     * @param mixed $default 默认值
     * @param callable $after 后置回调函数，其返回值将覆盖原字段值<br>
     * 回调函数格式为 function ($v, $k) {}<br>
     * </p>
     * @return mixed
     */
    protected function input(string $field, $default = '', callable $after = null)
    {
        return app(Request::class)->input($field, $default, $after);
    }

    /**
     * 从 $_GET, $_POST 获取请求参数，支持 payload
     * <p>
     * 简单用例：input('age') 即 $_POST['age'] <br>
     * 高级用例：input('post.age:i', 18, function ($val) { return $val+1; }) <br>
     * 即 $_POST['age']不存在时默认为18，最终返回 intval($_GET['age'])+1
     * @param string $field [(post|get|request).]<field_name>[.(i|b|a|f|d|s)]<br>
     * 参数池默认为 $_POST<br>
     * field_name 为字段名<br>
     * 类型强转：i=int, b=bool, a=array, f=float, d=double, s=string(默认)
     * @param mixed $default 默认值
     * @param callable $after 后置回调函数，其返回值将覆盖原字段值<br>
     * 回调函数格式为 function ($v, $k) {}<br>
     * </p>
     * @return Validator
     */
    protected function validate(string $field, $default = '', callable $after = null)
    {
        return app(Request::class)->validate($field, $default, $after);
    }

    /**
     * 获取所有请求参数，如果有验证则验证
     * @param bool $inParallel false: 以串联短路方式验证；true: 以并联方式验证，即使前面的验证不通过，也会继续验证后面的字段
     * @return array
     * @throws AppException
     */
    protected function request(bool $inParallel = false)
    {
        return app(Request::class)->request($inParallel);
    }

    /**
     * 网页后退
     * <p>`back()` 网页跳转回上一步</p>
     * <p>不要 `exit`</p>
     */
    protected function back()
    {
        header('Location: ' . getenv('HTTP_REFERER'));
    }

    /**
     * 网页跳转
     * <p>`redirect('/foo/bar')` 跳转到当前域名的`/foo/bar`地址去</p>
     * <p>`redirect('https://google.com')` 跳转到谷歌</p>
     * @param string $url
     */
    protected function redirect(string $url)
    {
        header('Location: ' . $url);
    }

}
