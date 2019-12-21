# Sparrow Framework
> PHP框架 Sparrow

![](../public/app/img/logo.svg)

## 安装

- `git clone --depth=1 https://github.com/muzk6/sparrow.git <project_name>`
- `composer install`

## 网站入口

- `public/index.php` 前台入口
- `public/admin.php` 后台入口
- 可以自定义入口

*注意：每个入口必须使用独立域名*

## 目录结构

Dir | Desc
--- | ---
app | 业务逻辑
app/Controllers | 控制器层，负责输入(请求参数，中间件)、处理(Service)、输出
app/Models | 模型层，1表1模型
app/Providers | 容器服务提供层
app/Services | 业务服务层
cli | 命令行脚本
config | 配置文件，通用配置放在当前目录下
config/dev | dev环境的配置
core | 框架文件
data | 缓存、日志数据目录，需要写权限
doc | 文档目录
lang | 国际化多语言
public | Web入口目录
rpc | RPC入口目录，禁止对外网开放
vendor | Composer库
views | 视图文件
workers | worker文件

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

## 路由

### 默认值规则

URI | Controller | Action
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
    // 后台路由组
    'admin' => [
        [
            // url: /secret, /secret/, /secret/index, /secret/index/
            'pattern' => '#^/secret/?(?<ac>[a-zA-Z_\d]+)?/?$#', // url: /secret
            'controller' => 'App\Controllers\Admin\IndexController',
        ]
    ],
];
```

- `app(\Core\Router::class)->dispatch();` 使用默认路由组 default
- `app(\Core\Router::class)->dispatch('admin');` 使用后台路由组 admin

### 路由相关信息

- `\Core\Router::getUrl` 返回请求的 url 路径
- `\Core\Router::getMatchGroups` 返回路由规则正则匹配项
- `\Core\Router::getMatchRule` 返回命中的路由规则

### 自定义404

在调用 `\Core\Router::dispatch` 之前调用 `\Core\Router::setStatus404Handler`

```php
app(\Core\Router::class)->setStatus404Handler(function () {
    echo '404';
})->dispatch();
```

## 请求参数 `Request params`
> 获取、过滤、表单验证、类型强转 请求参数 `$_GET,$_POST` 支持 `payload`

### 用例

```php
// 验证并且以集合返回，默认非短路式验证所有 input() 指定的字段，错误提示在异常 AppException::getData 里获取
input('get.foo:i')->required();
input('get.bar')->required()->setTitle('名字');
$inputs = request();

// 以串联短路方式验证（默认），遇到验证不通过时，立即终止后面的验证
input('get.foo:i')->required();
input('get.bar')->required()->setTitle('名字');
$inputs = request();

// 以并联方式验证，即使前面的验证不通过，也会继续验证后面的字段
input('get.foo:i')->required();
input('get.bar')->required()->setTitle('名字');
$inputs = request(true);
```

*串联短路结果*
```json
{
    "s": false,
    "c": 10001000,
    "m": "参数错误",
    "d": {
        "foo": "不能为空"
    }
}
```

*并联结果*
```json
{
    "s": false,
    "c": 10001000,
    "m": "参数错误",
    "d": {
        "foo": "不能为空",
        "bar": "名字不能为空"
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

- `\Core\BaseController::beforeAction`
- `\Core\BaseController::afterAction`

## `helper` 辅助函数用例

#### `app()` 容器

- `app(\App\Models\DemoModel::class)` 取 DemoModel 对象(只有 Model, AppPDO 的对象才是默认多例，容器的其它对象都是默认单例)
- `app_set(\App\Models\DemoModel::class, ...)` 设置(或重置)容器里的 DemoModel 对象，常用于单元测试 mock 对象

#### `config()` 配置文件

- `config('app.lang')`
- 假设当前环境是`dev`
- 依次搜索`config/app.php, config/dev/app.php`, 存在时返回第一个结果文件的内容，都不存在时返回`''`
- `config(['app.lang' => 'en'])`设置 run-time 的配置

#### `trans()` 多语言文本

- `trans(10001000)`
- 假设当前语言是`zh_CN`, 默认语言是`en`
- 依次搜索`lang/zh_CN.php, lang/en.php`, 存在`10001000`这个`key`时返回第一个结果内容，都不存在时返回`?`

#### `view()` 返回渲染后的视图HTML

- 需要安装`blade`库`composer require duncan3dc/blade`
- `view(string $view, array $params = [])`

#### `logfile()` 文件日志

- `logfile(uniqid(), ['foo', 'bar'], 'login')` 把内容写到`data/log/login_190328.log`
- 第1个参数为唯一值，可以通过这个值双向定位(定位代码位置、定位日志行位置)

#### `back()` 网页跳转回上一步

- `back()` 网页跳转回上一步
- 不要`exit`

#### `redirect()` 网页跳转到指定地址

- `redirect('/foo/bar')` 跳转到当前域名的`/foo/bar`地址去
- `redirect('https://google.com')` 跳转到谷歌

#### `url()` 带协议和域名的完整URL

- 当前域名URL：`url('path/to')`
- 其它域名URL：`url(['test', '/path/to'])`

#### `panic()` 直接抛出业务异常对象

- `panic('foo')` 等于 `new AppException('foo')`
- `panic('foo', ['bar'])` 等于 `new (AppException('foo'))->setData(['bar'])`
- `panic(10001000)` 等于 `new AppException('10001000')` 自动转为错误码对应的文本

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

#### `csrf_*()` XSRF

- `csrf_field()`直接生成 HTML
- `csrf_token()`生成 token
- `csrf_check()`效验，token 来源于 `$_SERVER['HTTP_X_CSRF_TOKEN'], $_POST['_token'], $_GET['_token'], $_REQUEST['_token']`

#### `flash_*()` 闪存，一性次缓存

- `flash_set(string $key, $value)` 闪存设置
- `flash_has(string $key)` 闪存是否存在
- `flash_get(string $key)` 闪存获取并删除
- `flash_del(string $key)` 闪存删除

## 数据库查询

### `PdoEngine`, `AppPDO`, `Model` 区别

- `app('pdo')` 或 `app(\PDO::class)` 或 `app(\Core\PdoEngine::class)` 返回 PdoEngine 对象，用法与原生 PDO 一致，同时支持分区，支持自动主从切换，但要注意防注入事项
- `app(\Core\AppPDO::class)` 返回 AppPDO 对象，内部组合于 PdoEngine 对象, 在其基础上封装了防注入的 增、删、改、查 的方法，可以使用 `->getEngine()` 返回 PdoEngine 对象
- `app(\App\Models\DemoModel::class)` 返回 DemoModel 对象，继承于 AppPDO, 在其基础上定义了 分区、库名、表名，可以通过覆盖 `->sharding()` 方法实现 分区、分库、分表 效果

### `Model`对象(推荐)
> 在 `AppPDO` 的基础上，封装成 `Model` 类(1个表对应1个 `Model`)自动进行 分区、分库、分表 (后面统称分表)

#### 配置说明 

- 参考 `app/Models/DemoModel.php`，配置类属性 `$table`
- 需要分表时，定义 `sharding` 规则，在子类覆盖 `\Core\BaseModel::sharding` 即可

#### 用例

用法与`AppPDO`一样，不同的是不需要再使用`->setTable()`来指定表

`$model->selectOne(['id=?', 1])`

### `PDO`对象
> 以下例子都是为了更好还原对比 sql 才用 PDO 对象，实际操作中强烈推荐使用 Model 对象

#### 查询1行所有列 `->selectOne()`
> 成功时返回第1行记录的数组

```php
$pdo = app(\Core\AppPDO::class);
// select * from table0 limit 1
$pdo->setTable('table0')->selectOne();

// select * from table0 where id = 1 limit 1
$pdo->setTable('table0')->selectOne('id=1'); // 有注入风险
$pdo->setTable('table0')->selectOne(['id=1']); // 有注入风险
$pdo->setTable('table0')->selectOne(['id=?', 1]); // 防注入
$pdo->setTable('table0')->selectOne(['id=?', [1]]); // 防注入
$pdo->setTable('table0')->selectOne(['id=:id', ['id' => 1]]); // 防注入
$pdo->setTable('table0')->selectOne(['id=:id', [':id' => 1]]); // 防注入

// 用 ->where() 指定条件
$pdo->setTable('table0')->where('id=1')->selectOne(); // 有注入风险
$pdo->setTable('table0')->where('id=?', 1)->selectOne(); // 防注入
$pdo->setTable('table0')->where->selectOne('id=:id', ['id' => 1]); // 防注入

// select * from table0 where id = 1 and (status=1 or type=2) limit 1
$pdo->setTable('table0')->where('id=?', 1)->where('(status=? or type=?)', 1, 2)->selectOne();

// select * from table0 where status=1 or type=2 limit 1 
$pdo->setTable('table0')->where('status=?', 1)->orWhere('type=?', 2)->selectOne();
```

#### 查询1行1列 `->selectColumn()`
> 成功时返回第1行第1列的值<br>
第二个参数`where`与`selectOne()`的`where`用法一样

```php
// select col1 from table0 limit 1
$pdo->setTable('table0')->selectColumn('col1');
$pdo->setTable('table0')->selectColumn(['col1']);

// select COUNT(1) from table0 limit 1
$pdo->setTable('table0')->selectColumn(['raw' => 'COUNT(1)']);
```

#### 查询多行 `->selectAll()`
> 成功时返回所有行的记录数组<br>
第二个参数`where`与`->selectOne()`的`where`用法一样

```php
// select col1, col2 from table0 order by col1, col2 desc
// ->orderBy('col1, col2') 等价于 ->append('order by col1, col2')
$pdo->setTable('table0')->orderBy('col1, col2')->selectAll('col1, col2');
$pdo->setTable('table0')->orderBy(['col1', 'raw' => 'col2'])->selectAll(['col1', 'col2']);

// select col1, COUNT(1) from table0 order by 1 desc
$pdo->setTable('table0')->orderBy(['raw' => '1 desc'])->selectAll(['col1', ['raw' => 'COUNT(1)']]);

// 查询多行(分页查询)的同时返回记录总行数
// select sql_calc_found_rows col1 from table0 limit 2
// select found_rows()
$pdo->setTable('table0')->limit(2)->selectCalc('col1');
```

#### 查询是否存在

```php
// select 1 from table0 limit 1
$pdo->setTable('table0')->exists('id=128'); // return true, false
```

#### 综合查询

```php
// select * from table0 where id > 100 order by col0 desc limit 0, 10 
$pdo->setTable('table0')->orderBy('col0 desc')->where('id>?', 100)->limit(10)->selectAll('*');

// select col0, col1 from table0 where name like 'tom%' group by col0 limit 0, 10 
$pdo->setTable('table0')->append('group by col0')->where('name like :name', ['name' => 'tom%'])->page(1, 10)->selectAll('col0, col1');

// select count(1) from table0
$pdo->setTable('table0')->count();
```

- `append('group by col0')`把自己的`sql`(支持`order by`, `group by`, `having`等等)拼接到`where`语句后面
- `limit(10)`等价于`limit([10]), limit(0, 10), limit([0, 10]), page(1, 10)`

#### `->getWhere()`, `->getLimit()` 方便拼接原生`sql`

- 调用`->where()`后可以通过`->getWhere()`获取`['name=?', ['foo']]`这种格式的条件
- 同理`->page()`, `->limit()` 也是可以通过`->getLimit()`返回` LIMIT 10,10`这个格式的字符串

```php
$pdo->where('code=?', $code);
$pdo->page(1, 5);
$where = $pdo->getWhere(); // 没有条件时返回 ['', null]
$limit = $pdo->getLimit();

$sql = "select SQL_CALC_FOUND_ROWS * from table0 {$where[0]} {$limit}"
$st = $pdo->prepare($sql);
$st->execute($where[1]);
var_dump($pdo->foundRows(), $st->fetchAll(2)); 
```

#### 插入 `->insert()`

```php
// insert into table0(col0) values(1)
$pdo->setTable('table0')->insert(['col0' => 1]);

// insert into table0(col0) values(1),(2)
$pdo->setTable('table0')->insert([ ['col0' => 1], ['col0' => 2] ]);

// insert into table0(ctime) values(UNIX_TIMESTAMP())
$pdo->setTable('table0')->insert(['ctime' => ['raw' => 'UNIX_TIMESTAMP()']]);
```

以下两个的用法与`->insert()`一致

- `->insertIgnore()` 即 `insert ignore ...`
- `->replace()` 即 `replace into ...`

#### 插入更新 `->insertUpdate()`

```php
// insert into table0(col0) values(1) on duplicate key update num = num + 1 
$pdo->setTable('table0')->insertUpdate(['col0' => 1], ['num' => ['raw' => 'num + 1']]);

// insert into table0(col0) values(1) on duplicate key update utime = UNIX_TIMESTAMP()
$pdo->setTable('table0')->insertUpdate(['col0' => 1], ['utime' => ['raw' => 'UNIX_TIMESTAMP()']);
```

#### 更新 `->update()`
> 默认必须要有 where

```php
// update table0 set col0 = 1 where id = 10
$pdo->setTable('table0')->where('id=?', 10)->update(['col0' => 1]);

// update table0 set num = num + 1 where id = 10
$pdo->setTable('table0')->where('id=?', 10)->update(['num' => ['raw' => 'num + 1']]);

// update table0 set utime = UNIX_TIMESTAMP() where id = 10
$pdo->setTable('table0')->where('id=?', 10)->update(['utime' => ['raw' => 'UNIX_TIMESTAMP()']]);
```

#### 删除 `->delete()`
> 默认必须要有 where

```php
// delete from table0 where id = 10
$pdo->setTable('table0')->where('id=?', 10)->delete();
```

#### 上一次查询的影响行数 `->affectedRows()`
> 在主库查询影响行数

```php
// select row_count()
$pdo->affectedRows();
```

*另外`->lastInsertId()`也会自动切换到主库查询上次插入的`id`*

#### 一次性强制使用主库 `->forceMaster()`
> 一般用于`select`语句，因为非`select`都已默认是主库

```php
// 在主库查询 select * from table0 limit 1
$pdo->forceMaster()->setTable('table0')->selectOne(['id=?', 1]);
```

#### 一次性切换分区 `->section()`
> 相关配置在`config/.../database.php`的`sections`里配置

```php
// 切换到分区 sec0
$pdo->section('sec0');
```

## 登录

```php
app(\Core\Auth::class)->login(1010); // 使用 1010 ID 使用
app(\Core\Auth::class)->getUserId(); // 1010
app(\Core\Auth::class)->isLogin(); // true
app(\Core\Auth::class)->logout(); // 退出登录
```

## RPC 远程过程调用

- 客户端参考`cli/rpc_client_demo.php`
- 服务端参考`rpc/rpc_server_demo.php`

## 消息队列

#### 发布消息

`app(\Core\Queue::class)->publish('SPARROW_QUEUE_DEMO', ['time' => microtime(true)]);`

#### 消费的worker

参考 `workers/SPARROW_QUEUE_DEMO.php`

建议规则：
- 每个 worker 只消费一个队列；
- 队列名与 worker名 一致，便于定位队列名对应的 worker 文件；
- 队列名与 worker名 要有项目名前缀，防止在 Supervisor, RabbitMq 里与其它项目搞混

## 请求时带上`Token`

使用以下任意一种方法

- `POST`请求通过表单参数`_token`，后端将从`$_POST['_token']`读取
- `GET`请求通过`?_token=`，后端将从`$_GET['_token']`读取
- 通过指定请求头`X-CSRF-Token`，后端将从`$_SERVER['HTTP_X_CSRF_TOKEN']`读取

## XDebug Trace
> 跟踪调试日志

以下任意方式可开启跟踪，日志位于`data/trace/`

*注意：请确保对 `data/` 目录有写权限*

### 跟踪 fpm

- 预先配置监听: `php cli/trace.php --help`，`--help` 查看帮助
- 当前URI 主动开启: `/?_xt=name0`，`name0`是当前日志的标识名
- Cookie 主动开启: `_xt=name0;`

*注意：`URI`, `Cookie` 方式的前提必须先设置 `config/dev/whitelist.php` 白名单 `IP`*

### 跟踪 rpc

在调用 `->request()` 前先调用 `->trace()` 即可

`app(\Core\Yar::class)->trace('rpc')->request('sparrow', 'bar', [1, 2, 3])`

### 跟踪 cli

`php demo.php --trace` 在任何脚本命令后面加上参数 `--trace` 即可

## 测试文件

- `cli/test.php` 为 `php-cli` 测试脚本
- `public/test.php` 为 `php-cgi` 测试入口

## 维护模式
> 开启维护模式，关闭网站访问入口

- `php cli/maintain.php`

## 环境与配置文件

以下为默认的环境配置，如果要自定义可以新建`config/env.php`，
把下面代码复制进去并修改即可

```php
if (is_file('/www/.pub.env')) { // publish
    define('APP_ENV', 'pub');
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
    ini_set('display_errors', 0);
} else { // develop
    define('APP_ENV', 'dev');
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}
```
