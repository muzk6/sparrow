# knf

PHP框架 KuiNiu Framework

#### 网站入口

- `public/index.php` 前台入口
- `public/admin.php` 后台入口

*注意：每个入口必须使用独立域名*

---

#### 路由

URI | Controller | Action
--- | --- | ---
`/foo` | `FooController` | `index()`
`/foo/` | `FooController` | `index()`
`/foo/bar` | `FooController` | `bar()`
`/foo/bar/` | `FooController` | `bar()`

---

#### 响应类型

- 通过`Controller`或其`action`的文档注释来声明响应类型
- `@page`为网页类型(默认类型)
- `@api`为 api格式 的 json字符串，支持控制器的方法直接`return`或`throw`出异常对象

##### 例子

```php
/**
 * @package App\Controllers
 * @api
 */
class IndexController extends BaseController
{
    /**
     * @page
     */
    public function index()
    {
        return 'Welcome Index';
    }
}
```

- 整个`IndexController`的方法都返回 api格式 的 json字符串(默认是网页类型)
- 只有`index()`重新声明了响应类型为`page`，所以这里返回的内容就是其`return`的内容 

---

#### 中间件

- `Controller`使用文档注释`@middleware`来声明中间件(eg. `@middleware name1,name2`)
- `action`使用文档注释`@get`或`@post`来声明中间件，同时`@get`,`@post`自身也是中间件(eg. `@post,name1,name2`)
- 中间件名称前面加`!`表示忽略对应的中间件(不支持`@get`,`@post`)，一般用于在方法忽略控制器声明的中间件
- 优先级 `action > Controller`
- 所有业务逻辑中不能使用`exit`或`throw`, 否则中间件不能正常工作 

##### 例子

```php
/**
 * @package App\Controllers
 * @middleware auth
 */
class IndexController extends BaseController
{
    /**
     * @get,!auth
     */
    public function index()
    {
        //todo
    }
}
```

- 整个`IndexController`的方法都要调用`auth`中间件
- `index()`只允许`GET`请求，且忽略`auth`中间件

##### 内置中间件

name | Desc
--- | ---
`get` | 限于 GET 请求
`post` | 限于 POST 请求
`auth` | 限于已登录

##### 自定义中间件

```php
// \App\Core\AppMiddleware

public function myMiddleware(Closure $next, array $context)
{
    //todo before;
    
    // 退出当前中间件用 return void, 不能 throw, 更不能 exit
    // return;
    $next();
    
    //todo after;
}
```

```php
// \App\Controllers\IndexController

/**
 * @get,myMiddelware
 */
public function index()
{
    //todo
}
```

- 在`\App\Core\AppMiddleware`定义中间件方法，参数`Closure $next, array $context`
- 中间件名字与前面定义的`AppMiddleware`方法名一致

---

#### XDebug Trace
> 跟踪调试日志

以下两种方法开启跟踪

- CLI `php cli/trace.php --help` 命令行开启，`--help` 查看帮助
- URI `/?xt=name0` URI开启，`name0`是当前日志的标识名

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
app/Controllers | 控制器层，负责输入(请求参数，中间件)、处理(Service)、输出
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
> 继承 Core 空间类，修改默认行为<br>

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

---

#### 环境与配置文件

以下为默认的环境配置，如果要自定义可以新建`app/Core/env.php`，
把下面代码复制进去并修改即可

```php
if (is_file('/www/PUB')) { // publish
    define('APP_ENV', 'pub');
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
    ini_set('display_errors', 0);
} else { // develop
    define('APP_ENV', 'dev');
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}
``` 