# knf

PHP框架 KuiNiu Framework

#### 控制器 URI

URI | Controller | Action
--- | --- | ---
`/foo` | `FooController` | `index()`
`/foo/` | `FooController` | `index()`
`/foo/bar` | `FooController` | `bar()`
`/foo/bar/` | `FooController` | `bar()`

---

#### 中间件
> 通过文档注释 @mw 即 middleware 来声明中间件

##### 例子

```php
/**
 * @mw auth
 * @package App\Controllers
 */
class IndexController extends BaseController
{
    /**
     * @mw get,!auth
     */
    public function index()
    {
        //todo
    }
}
```

- 整个`IndexController`的方法都要调用`auth`中间件
- `index()` 只允许`GET`请求，忽略`auth`中间件

##### 内置中间件

name | Desc
--- | ---
get | 限于 GET 请求
post | 限于 POST 请求
auth | 限于已登录
ignore | 忽略所有中间件，一般用于方法中

*前面加`!`表示忽略对应的中间件，一般用于方法中*

##### 自定义中间件

```php
// \App\Core\AppMiddleware

public function myMiddleware(array $context)
{
    return true;
}
```

```php
// \App\Controllers\IndexController

/**
 * @mw myMiddelware
 */
public function index()
{
    $data = DemoService::instance()->foo();
    echo json_response($data['name']);
}
```

- 在`\App\Core\AppMiddleware`定义中间件方法，参数`array $context`
- 在控制器中`@mw` 直接使用，中间件名字与前面定义的方法名一致

---

#### XDebug Trace
> 跟踪调试日志

以下两种方法开启跟踪
- CLI `php cli/trace.php`
- URI `?xt=<value>`

---

#### 维护模式
> 开启维护模式，关闭网站访问入口

- `php cli/maintain.php`

---

#### 常用常量

Name | Desc
--- | ---
TIME | `$_SERVER['REQUEST_TIME']`
IS_POST | 是否为 POST 请求
IS_GET | 是否为 GET 请求
IS_DEV | 是否为开发环境
APP_LANG | 当前语言 `eg. zh_CN`
APP_ENV | 当前环境 `eg. dev`
PATH_PUBLIC | 网站入口路径
PATH_DATA | 数据目录，有写权限
PATH_LOG | 日志目录

---

#### 目录结构

Dir | Desc
--- | ---
app | 业务逻辑
app/Controllers | 控制器层，负责输入(表单验证、权限控制……)、处理(Service)、输出
app/Models | 模型层，1表1模型
app/Services | 服务层，负责业务逻辑
app/Core | 框架核心类继承层
cli | 命令行脚本
config | 配置文件，通用配置放在当前目录下
config/dev | dev环境的配置
core | 框架核心文件，不可修改，若要修改默认行为请在 `app/Core` 里实现子类
data | 缓存、日志数据目录，需要写权限
lang | 国际化多语言
public | Web入口目录
rpc | RPC入口目录，禁止对外网开放
vendor | Composer库
views | 视图文件
workers | 长驻运行的脚本

---

#### Core 空间类继承
> 继承 Core 空间类，修复默认行为<br>

##### `AppXXX`

以 `\Core\AppMiddleware` 为例，在目录 `app/Core` 里新建同名类文件，
`\App\Core\AppMiddleware extends \Core\AppMiddleware`, 后下以覆盖类方法来修改父类的
默认行为

*注意 `\Core\AppException`, `\Core\AppPDO` 都是 `final class`，不可承继*

##### `BaseXXX`

由于 `BaseController`, `BaseModel`, `BaseService` 都是给业务逻辑类继承的，
记得在对最底层的类 `extends \App\Core\BaseXXX`，
例如 
- `\App\Core\BaseController extends \Core\BaseController extends`
- `\App\Controllers\IndexController extends \App\Core\BaseController`