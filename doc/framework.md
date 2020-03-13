# Sparrow Framework
> PHP框架 Sparrow

![](../public/app/img/logo.svg)

## 安装

- `git clone --depth=1 https://github.com/muzk6/sparrow.git <project_name>` 下载项目框架并命名新项目
    - `cd <project_name>`, `rm -rf .git` 删除原版本库
- `composer install` 安装基础依赖
- `docker-compose up -d nginx php-fpm` 部署基础环境
    - `docker-compose up -d` 或者部署完整环境，支持数据库、缓存、队列等服务
- http://localhost/ 开启主页
    - http://localhost:37062/ 运维后台，详情查看后面章节 `OPS 运维与开发`
    - http://localhost:37063/ 业务后台

![](https://raw.githubusercontent.com/muzk6/sparrow-res/master/img/home.png)

### 注意事项

- 确保项目目录 `data` 有**写**权限
- 为安全起见，修改对应环境文件 `config/dev/app.php` 的 `secret_key` 密钥
- 所有后台访问都有 IP 白名单，配置位于 `config/.../whitelist.php`

## 目录结构

Dir | Desc
--- | ---
app | 业务逻辑
app/Controllers | 控制器层，负责输入(请求参数，中间件)、处理(Service)、输出
app/Providers | 容器服务提供层
app/Services | 业务服务层
cli | 命令行脚本
config | 配置文件，通用配置放在当前目录下
config/dev | dev环境的配置
core | 框架文件
data | 缓存、日志数据目录，需要写权限
doc | 文档目录
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
PATH_PUBLIC | 网站入口路径
PATH_DATA | 数据目录，需要有写权限
PATH_LOG | 日志目录

## 测试文件

- `cli/test.php` 为 `php-cli` 测试脚本
- `public/test.php` 为 `php-cgi` 测试入口

## 路由

### 默认值规则

URL | Controller | Action
--- | --- | ---
`/` | `IndexController` | `index()`
`/foo` | `FooController` | `index()`
`/foo/` | `FooController` | `index()`
`/foo/bar` | `FooController` | `bar()`
`/foo/bar/` | `FooController` | `bar()`

### 自定义路由

*config/routes.php*
```php
/**
 * 路由配置
 *
 * 规则里必须定义以下任意一个类型：
 *
 * namespace: 全自动分发，指定命名空间，
 *  根据正则捕获命名分组 ct, ac (没指定命名分组时两者默认值均为 index)来自动分发到相应的控制器和方法
 *
 * controller: 半自动分发，指定控制器，
 *  根据正则捕获命名分组 ac (没指定命名分组时默认值为 index)来自动分发到相应的方法
 *
 * action: 手动分发，同时指定控制器和方法
 */

return [
    // 默认路由组
    'default' => [
        [
            // url: /
            'pattern' => '#^/$#',
            'action' => 'App\Controllers\IndexController@index',
        ],
        [
            // url: /foo, /foo/, /foo/bar, /foo/bar/
            'pattern' => '#^/(?<ct>[a-zA-Z_\d]+)/?(?<ac>[a-zA-Z_\d]+)?/?$#',
            'namespace' => 'App\Controllers\\',
        ],
    ],
    // 其它路由组
    'secret' => [
        [
            // url: /secret, /secret/, /secret/index, /secret/index/
            'pattern' => '#^/secret/?(?<ac>[a-zA-Z_\d]+)?/?$#',
            'controller' => 'App\Controllers\Secret\IndexController',
        ]
    ],
];
```

- `app(\Core\Router::class)->dispatch();` 使用默认路由组 default
- `app(\Core\Router::class)->dispatch('secret');` 使用路由组 secret

### 路由相关信息

- `\Core\Router::getUrl` 返回请求的 url 路径
- `\Core\Router::getMatchGroups` 返回路由规则正则匹配项
- `\Core\Router::getMatchRule` 返回命中的路由规则

### 自定义404

在调用 `\Core\Router::dispatch` 之前调用 `\Core\Router::setStatus404Handler`

```php
app(\Core\Router::class)->setStatus404Handler(function () {
    http_response_code(404);
})->dispatch();
```

## 请求参数
> 获取、过滤、表单验证、类型强转 请求参数 `$_GET,$_POST` 支持 `payload`

- 以下 `$this` 指的是 `Controller` 对象
- 以下的验证失败时会抛出异常 \Core\AppException

### 不验证，一个一个获取

```php
$firstName = input('post.first_name');
$lastName = input('last_name');
var_dump($firstName, $lastName);exit;
```

### 不验证，全部获取

```php
input('post.first_name');
input('last_name');
$request = request();
var_dump($request);exit;
```

### 部分验证，一个一个获取

```php
$firstName = input('post.first_name');
$lastName = validate('last_name')->required()->setTitle('名字')->get();
var_dump($firstName, $lastName);exit;
```

### 部分验证，全部获取

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

## 控制器方法 action 用例

- 支持自动依赖注入

参考 `app/Controllers/DemoController.php`

### 自定义勾子

覆盖控制器基类勾子方法即可

- `\Core\BaseController::beforeAction` 当且仅当返回 false 时，将终止后面的 action 调用(afterAction 不受影响)
- `\Core\BaseController::afterAction`

## 数据库查询

- 配置文件 `config/.../database.php`
- 用例参考 `tests/feature/db.php`

## `helpers` 其它辅助函数用例

#### `app()` 容器

- `app(\App\Services\DemoService::class)` 取 DemoService 单例对象，自动定义容器元素和依赖注入。如果需要手动定义，可以在 `app/Providers` 里定义
- `app(\App\Services\DemoService::class, $value)` 设置(或重置)容器里的元素，常用于单元测试 mock 对象

#### `config()` 配置文件

- `config('app.lang')`
- 假设当前环境是`dev`
- 依次搜索`config/app.php, config/dev/app.php`, 存在时返回第一个结果文件的内容，都不存在时返回`''`
- `config(['app.lang' => 'en'])`设置 run-time 的配置

添加新环境配置：
复制目录 `config/dev` 及其配置文件，在 `config/env.php` 中添加多一个新环境分支

#### `trans()` 多语言文本

- `trans(10001000)`
- 假设当前语言是`zh_CN`, 默认语言是`en`
- 依次搜索`lang/zh_CN.php, lang/en.php`, 存在`10001000`这个`key`时返回第一个结果内容，都不存在时返回`?`

#### `logfile()` 文件日志

- `logfile(uniqid(), ['foo', 'bar'], 'login')` 把内容写到`data/log/login_190328.log`
- 第1个参数为唯一值，可以通过这个值双向定位(定位代码位置、定位日志行位置)

#### `url()` 带协议和域名的完整URL

- 当前域名URL：`url('path/to')`
- 其它域名URL：`url(['test', '/path/to'])`

#### `panic()` 直接抛出业务异常对象

- `panic('foo')` 等于 `new AppException('foo')`
- `panic('foo', ['bar'])` 等于 `new (AppException('foo'))->setData(['bar'])`
- `panic(10001000)` 等于 `new AppException('10001000')` 自动转为错误码对应的文本

*注意：强烈建议使用 `panic` 或 `AppException` 抛出异常，不要使用 `Exception`, 否则会有业务外的错误返回到客户端，引起安全风险！*

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

```php
request_flash();
old('name', $data['name']);
```

#### `csrf_*()` CSRF, XSRF

- `csrf_field()`直接生成 HTML
- `csrf_token()`生成 token
- `csrf_check()`效验，token 来源于 `$_SERVER['HTTP_X_CSRF_TOKEN'], $_POST['_token'], $_GET['_token'], $_REQUEST['_token']`

请求时带上 `Token`, 使用以下任意一种方法

- `POST`请求通过表单参数`_token`，后端将从`$_POST['_token']`读取
- `GET`请求通过`?_token=`，后端将从`$_GET['_token']`读取
- 通过指定请求头`X-CSRF-Token`，后端将从`$_SERVER['HTTP_X_CSRF_TOKEN']`读取2

#### `flash_*()` 闪存，一性次缓存

- `flash_set(string $key, $value)` 闪存设置
- `flash_has(string $key)` 闪存是否存在
- `flash_get(string $key)` 闪存获取并删除
- `flash_del(string $key)` 闪存删除

#### `api_format()`, `api_json()` 格式化为接口输出的内容结构

- `api_format(true, ['foo' => 1])` 格式化为成功的内容结构 array
- `api_format($exception)` 格式化异常对象为失败的内容结构 array
- `api_json()`, `api_format()` 用法一样，区别是返回 string-json
- `api_success()`, `api_error()` 是 `api_json()` 的简写

*成功提示，在控制器 action 里的等价写法如下：*

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

```php
public function successAciton()
{
    return ['foo' => 1]; // 只能返回消息体 d
    return api_success('', 0, ['foo' => 1]); // 支持返回 c, m ,d; 一般用于方便返回纯 m, 例如 api_success('我是成功消息');
    return api_json(true, ['foo' => 1]); // 支持返回 s, c, m ,d
}
```

*错误提示等价写法如下：*

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

```php
public function errorAciton()
{
    panic('我是失败消息', ['foo' => 1]); // 直接抛出异常，不用 return, 如果使用错误码，错误码必须存在于 `lang/` 配置里
    return api_error('我是失败消息', 0, ['foo' => 1]); // 支持返回 c, m ,d; 可自由指定错误码
    return api_json(false, ['foo' => 1]); // 支持返回 s, c, m ,d
}
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

`app(Redis::class)->setex('key', 3600, 'value')` 与原生一致

## 登录

```php
app(\Core\Auth::class)->login(1010); // 使用 1010 ID 使用
app(\Core\Auth::class)->getUserId(); // 1010
app(\Core\Auth::class)->isLogin(); // true
app(\Core\Auth::class)->logout(); // 退出登录
```

## RPC 远程过程调用

### 依赖

- CentOS
    - `yum -y install curl-devel`
- Ubuntu
    - `sudo apt install libcurl4-gnutls-dev`
    - Ubuntu 17 还要兼容一下 curl 的安装路径 `sudo ln -s /usr/include/x86_64-linux-gnu/curl /usr/include/`
- `pecl install msgpack`
- `pecl install yar`
    - 安装过程中建议选择 `yes` 使用 msgpack
    
### 用例

- 客户端参考 `cli/rpc_client_demo.php`
    - 参考配置文件 `config/dev/yar.php`
- 服务端参考 `rpc/rpc_server_demo.php`
    - 配置 http 入口 `private/rpc` 以及独立域名

## 消息队列

### 依赖

`composer require php-amqplib/php-amqplib`

### 配置

`config/.../rabbitmq.php`

### 用例

- `app(\Core\Queue::class)->publish('SPARROW_QUEUE_DEMO', ['time' => microtime(true)]);` 发布消息
- 消费的 worker, 参考 `workers/SPARROW_QUEUE_DEMO.php`
- docker 容器 php-fpm 里面已经有 supervisor, 使 worker 变为长驻进程
    - 示例配置文件为 `docker/php-fpm/supervisor_conf.d/SPARROW_QUEUE_DEMO.conf`
    - 日志可通过"运维与开发"后台查看，或者在 supervisorctl 里面使用 tail 命令查看

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

## Elasticsearch, es

### 依赖

`composer require elasticsearch/elasticsearch`

### 文档

https://github.com/elastic/elasticsearch-php

## OPS 运维与开发
> 用于运维监控与开发调试，包括 日志、调试、性能分析

![](https://raw.githubusercontent.com/muzk6/sparrow-res/master/img/ops.png)

- 默认地址为 http://localhost:37062/
    - 可自行修改 nginx 配置：`docker/nginx/conf.d/ops.sparrow.conf`
    - 注意安全性，端口和域名不要对外开放
- 登录密码为 `ops.sparrow`, 建议开发者修改这个默认密码(位于 `\App\Controllers\OPS\IndexController::LOGIN_PASSWD`)

### XDebug Trace
> 跟踪调试日志

以下任意方式可开启跟踪，日志位于`data/trace/`

*注意：请确保对 `data/` 目录有写权限*

#### 跟踪 fpm

- 预先配置监听: `php cli/trace.php --help`，`--help` 查看帮助
- 当前URL 主动开启: `/?_xt=name0`，`name0`是当前日志的标识名
- Cookie 主动开启: `_xt=name0;`

*注意：`URL`, `Cookie` 方式的前提必须先设置 `config/.../whitelist.php` 白名单 `IP`*

#### 跟踪 rpc

在调用 `->request()` 前先调用 `->trace()` 即可

`app(\Core\Yar::class)->trace('rpc')->request('sparrow', 'bar', [1, 2, 3])`

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

## 维护模式
> 开启维护模式，关闭网站访问入口

- `php cli/maintain.php`
