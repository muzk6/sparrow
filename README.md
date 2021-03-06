# Sparrow Framework
> [PHP框架 Sparrow](https://github.com/muzk6/sparrow)

![](./public/app/img/logo.svg)

## 安装

### 创建项目

`composer create-project --prefer-dist muzk6/sparrow your-projectname`

*请确保项目目录 `data` 有**写**权限*

### 使用 docker-compose 部署开发环境

- `docker-compose up -d nginx php-fpm` 部署基础环境
- 或者部署完整环境 `docker-compose up -d`，支持数据库、缓存、队列等服务

*不建议生产环境使用 docker 部署*

### 访问链接

- http://localhost:37061/ 项目主页
- http://localhost:37062/ 运维后台
    - 登录密码位于 `app/Routes/OPS/index.php` 常量 `LOGIN_PASSWD`，可自行修改
    - 详情查看后面章节 `OPS 运维与开发`
- http://localhost:37063/ 业务后台

*所有后台访问都有 IP, Cookie 白名单，配置位于 `config/.../whitelist.php`*<br>

![](https://raw.githubusercontent.com/muzk6/sparrow-res/master/img/home.png)

## 目录结构

Dir | Desc
--- | ---
app | 业务逻辑
app/Providers | 容器服务提供层
app/Routes | 路由层(控制器层)
app/Services | 业务服务层
cli | 命令行脚本
config | 配置文件，通用配置放在当前目录下
config/dev | dev环境的配置
core | 框架文件
data | 缓存、日志数据目录，需要写权限
lang | 国际化多语言
private | 私有 Web 入口目录，配置的域名不应该被外网访问
private/admin | Admin 后台入口
private/rpc | RPC 入口目录
public | 公有 Web 入口目录
tests | 单元测试
vendor | Composer库
views | 视图文件
workers | worker 文件

## 常用常量

Name | Desc
--- | ---
TIME | `$_SERVER['REQUEST_TIME']`, 脚本启动时间，不能在 worker 里使用，否则不会变化
IS_POST | 是否为 POST 请求
IS_GET | 是否为 GET 请求
IS_DEV | 是否为开发环境
APP_LANG | 当前语言 `eg. zh_CN`
APP_ENV | 服务器环境 `eg. dev`
TEST_ENV | 是否为单元测试的环境
PATH_APP | 项目业务目录
PATH_ROUTES | 路由目录
PATH_PUBLIC | 网站入口路径
PATH_DATA | 数据目录，需要有写权限
PATH_LOG | 日志目录

## 测试文件

- `cli/test.php` 为 `php-cli` 测试脚本
- `public/test.php` 为 `php-cgi` 测试入口

## 路由

### 注册路由

- `route_get()` 注册回调 GET 请求
    - `route_get_re()` 正则匹配
- `route_post()` 注册回调 POST 请求
    - `route_post_re()` 正则匹配 
- `route_any()` 注册回调任何请求
    - `route_any_re()` 正则匹配
- `route_middleware()` 注册路由中间件，顺序执行，组内优先
- `route_group()` 路由分组，隔离中间件

### 用例

```php
route_middleware(function () {
    echo '中间件a1';
});

route_middleware(function () {
    echo '中间件a2';
});

route_group(function () {
    route_middleware(function () {
        echo '中间件b1';
    });

    route_get('/', function () {
        return 'Just Do It!';
    });
});

route_middleware(function () {
    echo '中间件a3';
});
```

输出如下：
```
中间件b1
中间件a1
中间件a2
Just Do It!
中间件a3
```

- 基本用法参考 `app/Routes/index.php`
- 高级用法参考测试用例: `tests/feature/router_advanced.php`
- 如果要实现 MVC 的 Controller::action 模式，可参考 `tests/feature/router_mvc.php`

### 自定义404

在调用 `app(Router::class)->dispatch()` 之前像下面例子设置 404 回调：

```php
app(Router::class)->setStatus404Handler(function () {
    return '自定义404页面'; // return view('...')
});
```

## 请求参数
> 获取、过滤、表单验证、类型强转 请求参数 `$_GET,$_POST` 支持 `payload`

- 以下的验证失败时会抛出异常 \Core\AppException

### 不验证，一个一个获取

```php
$firstName = input('post.first_name');
$lastName = input('last_name');
var_dump($firstName, $lastName);exit;
```

### 不验证，统一获取

```php
input('post.first_name');
input('last_name');
$request = request();
var_dump($request);exit;
```

### 部分验证，一个一个获取

```php
$firstName = input('post.first_name');
$lastName = validate('last_name')->required()->get('名字');
var_dump($firstName, $lastName);exit;
```

### 部分验证，统一获取

```php
input('post.first_name');
validate('last_name')->required()->setTitle('名字');
$request = request();
var_dump($request);exit;
```

### 串联短路方式验证（默认）

遇到验证不通过时，立即终止后面的验证

```php
validate('post.first_name')->required();
validate('last_name')->required()->setTitle('名字');
$request = request(); // 以串联短路方式验证
```

*串联结果*
```json
{
    "s": false,
    "c": 10001000,
    "m": "参数错误",
    "d": {
        "first_name": "不能为空"
    }
}
```

### 并联验证

即使前面的验证不通过，也会继续验证后面的字段

```php
validate('post.first_name')->required();
validate('last_name')->required()->setTitle('名字');
$request = request(true); // 以并联方式验证
```

*并联结果*
```json
{
    "s": false,
    "c": 10001000,
    "m": "参数错误",
    "d": {
        "first_name": "不能为空",
        "last_name": "名字不能为空"
    }
}
```

### `input()` 参数说明

`'get.foo:i'` 中的类型转换`i`为整型，其它类型为：

Name | Type
--- | ---
i | int
s | string
b | bool
a | array
f | float
d | double

## PDO 数据库

可以配置 MySQL, SQLite 等 PDO 支持的数据库

- 配置文件 `config/.../mysql.php`
- 用例参考 `tests/feature/db.php`

如果想同时使用 SQLite 等数据库, 参考复制 `mysql.php` 为新的数据库配置文件，按需配置 dsn，再注册容器即可(参考 `\Core\ServiceProvider` 的 `PDOEngine`)

## `helpers` 其它辅助函数用例

#### `app()` 容器

- `app(\App\Services\DemoService::class)` 取 DemoService 单例对象，自动定义容器元素和依赖注入。如果需要手动定义，可以在 `app/Providers` 里定义
- `app(\App\Services\DemoService::class, $value)` 设置(或重置)容器里的元素，常用于单元测试 mock 对象

#### `config()` 配置文件

- `config('app.lang')`
- 假设当前环境是`dev`
- 依次搜索`config/dev/app.php, config/app.php`, 存在时返回第一个结果文件的内容，都不存在时返回`''`
- `config(['app.lang' => 'en'])`设置 run-time 的配置

添加新环境配置：
复制目录 `config/dev` 及其配置文件，在 `config/env.php` 中添加多一个新环境分支

#### `trans()` 多语言文本

- `trans(10001000)`
- 假设当前语言是`zh_CN`, 默认语言是`en`
- 依次搜索`lang/zh_CN.php, lang/en.php`, 存在`10001000`这个`key`时返回第一个结果内容，都不存在时返回`?`

#### `logfile()` 文件日志

`logfile('test', ['foo', 'bar'], 'login')` 把内容写到`data/log/login_20190328.log`

各日志文件说明：

- `standard_xxx.log` PHP 标准错误处理程序写的日志，比较精简，但只能它才能记录 Fatal Error, Parse Error
- `error_xxx.log` 框架写的错误日志，比较详细
- `access_xxx.log` 框架的写访问日志
- `app_xx.log` 用户写的默认日志，文件名可以修改，由 `logfile()` 参数3控制 

#### `url()` 带协议和域名的完整URL

- 当前域名URL：`url('path/to')`
- 其它域名URL：`url(['test', '/path/to'])`

#### `panic()` 直接抛出业务异常对象

- `panic(10001000)` 等于 `throw new AppException('10001000')` 自动转为错误码对应的文本，参考翻译文件 lang/zh_CN.php
- `panic('foo')` 等于 `throw new AppException('foo')`
- `panic('foo', ['bar'])` 等于 `throw (new AppException('foo'))->setData(['bar'])`

`AppException` 异常属于业务逻辑，能够作为提示通过接口返回给用户看，而其它异常则不会(安全考虑)

#### `inject()` 支持自动依赖注入的函数调用

通过回调函数的形参里声明类型，就能会自动注入

```php
inject(function (\Core\Queue $queue) {
    //todo...
});
```

#### `request_flash()`, `old()` 记住并使用上次的请求参数

- `request_flash()` 把本次请求的参数缓存起来
- `old(string $name = null, string $default = '')` 上次请求的字段值

#### `csrf_*()` CSRF, XSRF

- `csrf_field()`直接生成 HTML
- `csrf_token()`生成 token
- `csrf_check()`效验，token 来源于 `$_SERVER['HTTP_X_CSRF_TOKEN'], $_POST['_token'], $_GET['_token'], $_REQUEST['_token']`

请求时带上 `Token`, 使用以下任意一种方法

- `POST` 请求通过表单参数 `_token`, 后端将从 `$_POST['_token']` 读取
- `GET` 请求通过 `?_token=`, 后端将从 `$_GET['_token']` 读取
- 通过指定请求头 `X-CSRF-Token`, 后端将从 `$_SERVER['HTTP_X_CSRF_TOKEN']` 读取

#### `flash_*()` 闪存，一性次缓存

- `flash_set(string $key, $value)` 闪存设置
- `flash_has(string $key)` 存在且为真
- `flash_exists(string $key)` 闪存是否存在，即使值为 null
- `flash_get(string $key)` 闪存获取并删除
- `flash_del(string $key)` 闪存删除

#### `api_format()`, `api_json()` 格式化为接口输出的内容结构

- `api_format(true, ['foo' => 1])` 格式化为成功的内容结构 array
- `api_format($exception)` 格式化异常对象为失败的内容结构 array
- `api_json()`, `api_format()` 用法一样，区别是前者返回 string-json
- `api_success()`, `api_error()` 是 `api_json()` 的简写

#### 成功提示

```json
{
    "s": true,
    "c": 0,
    "m": "",
    "d": {
        "foo": 1
    }
}
```

路由里等价写法如下：

```php
return ['foo' => 1]; // 只能返回消息体 d
return api_success('', 0, ['foo' => 1]); // 一般用于方便返回纯 m, 例如 api_success('我是成功消息');
return api_json(true, ['foo' => 1]);
```

#### 错误提示

```json
{
    "s": false,
    "c": 0,
    "m": "我是失败消息",
    "d": {
        "foo": 1
    }
}
```

路由里等价写法如下：

```php
panic('我是失败消息', ['foo' => 1]); // 直接抛出异常，不用 return; 另一种便捷的用法是 panic(10001000);
return api_error('我是失败消息', 0, ['foo' => 1]); // 可自由指定错误码
return api_json(false, ['foo' => 1]);
```

#### `assign()`, `view()` 模板与变量

- `assign('firstName', 'Hello')` 定义模板变量
- `return view('demo', ['title' => $title])` 定义模板变量的同时返回渲染内容

#### `back()`, `redirect()` 网页跳转

- `return back()` 跳转回上一步
- `return redirect('/demo')` 跳转到 `/demo`

## 缓存 redis

### 依赖

`pecl install redis`

### 用例

`app(\Core\AppRedis::class)->setex('key', 3600, 'value')` 与原生一致

## 登录

```php
app(\Core\Auth::class)->login(1010); // 登录 ID 为 1010
app(\Core\Auth::class)->getUserId(); // 1010
app(\Core\Auth::class)->isLogin(); // true
app(\Core\Auth::class)->logout(); // 退出登录
```

## RPC 远程过程调用

- 服务端入口 `private/rpc/index.php`, 注意要使用内部域名，不能让外网访问
- 客户端调用参考 `tests/feature/curl.php`

## 消息队列

worker 遇到信号 `SIGTERM`, `SIGHUP`, `SIGINT`, `SIGQUIT` 会平滑结束进程。
如果要强行结束可使用信号 `SIGKILL`, 命令为 `kill -s KILL <PID>`

### 依赖

`composer require php-amqplib/php-amqplib`

### 配置

`config/.../rabbitmq.php`

### 用例

- `queue_publish('SPARROW_QUEUE_DEMO', ['foo' => 1, 'bar' => 2]);` 发布消息
- 消费的 worker, 参考 `workers/SPARROW_QUEUE_DEMO.php`
- docker 容器 php-fpm 里面已经有 supervisor, 使 worker 变为长驻进程
    - 示例配置文件为 `docker/php-fpm/supervisor_conf.d/SPARROW_QUEUE_DEMO.conf`
    - 日志可通过"运维与开发"后台查看，或者在 supervisorctl 里面使用 tail 命令查看
    - 必须先启动 rabbitmq 服务，再启动 worker, 否则会报错。如果遇到 rabbitmq 容器比 php-fpm 容器先启动的情况，执行这个命令即可: `docker-compose exec php-fpm bash -c "supervisorctl start all"`

建议规则：
- 每个 worker 只消费一个队列；
- 队列名与 worker名 一致，便于定位队列名对应的 worker 文件；
- 队列名与 worker名 要有项目名前缀，防止在 Supervisor, RabbitMq 里与其它项目搞混

## 邮件 email

### 依赖

`composer require swiftmailer/swiftmailer`

### 配置

`config/.../email.php`

### 用例

参考类文档 `\Core\Mail`

## OPS 运维与开发
> 用于运维监控与开发调试，包括 日志、调试、性能分析

![](https://raw.githubusercontent.com/muzk6/sparrow-res/master/img/ops.png)

- 默认地址为 http://localhost:37062/
- 可自行修改 nginx 配置：`docker/nginx/conf.d/ops.sparrow.conf`
- 注意安全性，端口和域名不要对外开放

### XDebug
> 断点调试

配置文件位置 `docker/php-fpm/php_ini/xdebug.ini`

### XDebug Trace
> 跟踪调试日志

以下任意方式可开启跟踪，
日志可在运维后台 `XDebug - 跟踪文件` 查看， 
或者直接在项目目录 `data/trace/` 里查看

*注意：请确保对 `data/` 目录有写权限*

#### 跟踪 fpm

- 预先配置监听，两种方法设置:
    - 在运维后台 `XDebug - 监听设置` 
    - 命令行 `php cli/trace.php`，参数 `--help` 查看帮助
- 当前URL 主动开启: `/?_xt=name0`，`name0`是当前日志的标识名
- Cookie 主动开启: `_xt=name0;`

*注意：`URL`, `Cookie` 方式的前提必须先设置 `config/.../whitelist.php` 白名单 `IP` 或 白名单 `Cookie`*

#### 跟踪 cli

`php demo.php --trace` 在任何脚本命令后面加上参数 `--trace` 即可

### XHProf

#### 依赖

- [扩展 tideways_xhprof](https://github.com/tideways/php-xhprof-extension/releases)
- GUI - View Full Callgraph 功能，需要安装 `graphviz`
    - Ubuntu: `sudo apt install graphviz`
    - CentOS: `yum install graphviz`

#### 使用

- 配置文件 `config/.../xhprof.php`
- `enable` 设置为 `true`, 即可记录大于指定耗时的请求

*注意：请确保对 `data/` 目录有写权限*

## 维护模式
> 开启维护模式，关闭网站访问入口

- `php cli/maintain.php`
