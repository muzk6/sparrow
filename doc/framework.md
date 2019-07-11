# Sparrow Framework
> PHP框架 Sparrow

![](../public/app/img/logo.svg)

## 网站入口

- `public/index.php` 前台入口
- `public/admin.php` 后台入口

*注意：每个入口必须使用独立域名*

## 路由

URI | Controller | Action
--- | --- | ---
`/` | `IndexController` | `index()`
`/foo` | `FooController` | `index()`
`/foo/` | `FooController` | `index()`
`/foo/bar` | `FooController` | `bar()`
`/foo/bar/` | `FooController` | `bar()`

## XDebug Trace
> 跟踪调试日志

以下任意方式可开启跟踪，日志位于`data/trace/`

### 跟踪fpm

- 预先配置监听: `php cli/trace.php --help`，`--help` 查看帮助
- 当前URI 主动开启: `/?_xt=name0`，`name0`是当前日志的标识名
- Cookie 主动开启: `_xt=name0;`

*注意：`URI`, `Cookie`方式的的前提必须先设置`config/dev/whitelist.php`白名单`IP`*

### 跟踪cli

`php demo.php --trace` 在任何脚本命令后面加上参数 `--trace` 即可

## 维护模式
> 开启维护模式，关闭网站访问入口

- `php cli/maintain.php`

## 测试

- `cli/test.php` 为 `php-cli` 测试脚本
- `public/test.php` 为 `php-cgi` 测试入口

## 常用常量

Name | Desc
--- | ---
TIME | `$_SERVER['REQUEST_TIME']`
IS_POST | 是否为 POST 请求
IS_GET | 是否为 GET 请求
IS_DEV | 是否为开发环境
APP_LANG | 当前语言 `eg. zh_CN`
APP_ENV | 服务器环境 `eg. dev`
TEST_ENV | 是否为单元测试的环境
PATH_PUBLIC | 网站入口路径
PATH_DATA | 数据目录，需要有写权限
PATH_LOG | 日志目录
TIME | 脚本启动时间，不能在 worker 里使用，否则不会变化

## 目录结构

Dir | Desc
--- | ---
app | 业务逻辑
app/Controllers | 控制器层，负责输入(请求参数，中间件)、处理(Service)、输出
app/Events | 事件层，用于写业务逻辑、单元测试
app/Models | 模型层，1表1模型
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

## 环境与配置文件

以下为默认的环境配置，如果要自定义可以新建`config/env.php`，
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

## 事件
> 用于写业务逻辑，支持异步

#### 定义监听

- 参考`app/Events/DemoEvent.php`定义事件类
- `\App\Events\DemoEvent::handle`支持自动依赖注入
- `$params` 为 `event()` 传入的参数，`$params = ['p1' => 1, 'p2' => 2]`

```php
public function handle(DemoModel $demoModel, array $params)
{
    return $demoModel->selectOne(['name like ?', "{$params['name']}%"]);
}
```

#### 发送事件

```php
event(\App\Events\DemoEvent::class, ['p1' => 'test']); // 返回 `\App\Events\DemoEvent::handle` 的结果

event(\App\Events\DemoEvent::class, ['p1' => 'test'], true) // 返回 null, 参数将进入异步队列，对应 worker 通过 cli/worker.php 脚本启动
```

- 异步worker 所有 include 的文件有变化时，会自动 exit. 因此 worker 必须使用 supervisor 管理
- 所有事件参数必须放到数组里面去

## CSRF(XSRF)

#### 获取`Token`

使用以下任意一种方法

- `csrf_field()`直接生成`HTML`
- `csrf_token()`生成`Token`
- `csrf_check()`效验

#### 请求时带上`Token`

使用以下任意一种方法

- `POST`请求通过表单参数`_token`，后端将从`$_POST['_token']`读取
- `GET`请求通过`?_token=`，后端将从`$_GET['_token']`读取
- 通过指定请求头`X-CSRF-Token`，后端将从`$_SERVER['HTTP_X_CSRF_TOKEN']`读取

## `helper` 辅助函数用例

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

#### app('app.aes') AES加密解密对象

- `$data = app('app.aes')->encrypt('foo')` 加密返回密串和初始向量
- `app('app.aes')->decrypt($data['cipher'], $data['iv'])` 解密

#### `back()` 网页跳转回上一步

- `back()` 网页跳转回上一步
- 不要`exit`

#### `redirect()` 网页跳转到指定地址

- `redirect('/foo/bar')` 跳转到当前域名的`/foo/bar`地址去
- `redirect('https://google.com')` 跳转到谷歌

#### `panic()` 直接抛出业务异常对象

- `panic('foo')` 等于 `new AppException('foo')`
- `panic('foo', ['bar'])` 等于 `new (AppException('foo'))->setData(['bar'])`
- `panic(10001000)` 等于 `new AppException('10001000')` 自动转为错误码对应的文本

#### RPC 远程过程调用

- 客户端参考`cli/rpc_client_demo.php`
- 服务端参考`rpc/rpc_server_demo.php`

## 数据库查询
> `AppPDO`支持`PDO`对象的所有方法、且自动切换主从(所有`select`连接从库)、能更方便地防注入<br>
以下所有方法只支持单表操作，需要连表操作请使用原生SQL(原因是使用封装好的连表查询会大大增加复杂度和开发成本)

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

// 这里用到的 ->where(), 仅当 ->selectOne() 参数为 null(默认) 时生效，其它查询同理
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

#### 查询多行 `->selectColumn()`
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
select 1 from table0 limit 1
$pdo->setTable('table0')->exists('id=128'); // return true, false
```

#### 综合查询

```php
// select * from table0 where id > 100 order by col0 desc limit 0, 10 
$pdo->setTable('table0')->append('order by col0 desc')->limit(10)->selectAll('*', ['id>?', 100]);

// select col0, col1 from table0 where name like 'tom%' group by col0 limit 0, 10 
$pdo->setTable('table0')->append('group by col0')->page(1, 10)->selectAll('col0, col1', ['name like :name', ['name' => 'tom%']]);

// select count(1) from table0
$pdo->setTable('table0')->count();
```

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

- `append('order by col0 desc')`把自己的`sql`(还支持`group by, having`等等)拼接到`where`语句后面
- `limit(10)`等价于`limit([10]), limit(0, 10), limit([0, 10]), page(1, 10)`

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
> 第二个参数`where`与`selectOne()`的`where`用法一样

```php
// update table0 set col0 = 1 where id = 10
$pdo->setTable('table0')->update(['col0' => 1], ['id=?', 10]);

// update table0 set num = num + 1 where id = 10
$pdo->setTable('table0')->update(['num' => ['raw' => 'num + 1']], ['id=?', 10]);

// update table0 set utime = UNIX_TIMESTAMP() where id = 10
$pdo->setTable('table0')->update(['utime' => ['raw' => 'UNIX_TIMESTAMP()']], ['id=?', 10]);
```

#### 删除 `->delete()`
> 参数`where`与`selectOne()`的`where`用法一样

```php
// delete from table0 where id = 10
$pdo->setTable('table0')->delete(['id=?', 10]);
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

---

### `Model`数据库查询
> 在`$pdo`的基础上，封装成`Model`类(1个表对应1个`Model`)自动进行 分区、分库、分表 (后面统称分表)

#### 配置说明 

- 参考`app/Models/DemoModel.php`，配置类属性`$table`
- 需要分表时，定义`sharding`规则，在子类覆盖`\Core\BaseModel::sharding`即可

#### 用例

用法与`AppPDO`一样，不同的是不需要再使用`->setTable()`来指定表

`$model->selectOne(['id=?', 1])`

## 请求参数`Request params`
> 获取、过滤、验证、类型强转 请求参数`$_GET,$_POST`支持`payload`

#### 用例

```php
// 不验证直接返回
$foo = input('get.foo:i');

// 验证并且以集合返回
$req = validate(function () {
    input('get.foo:i')->required();
    input('get.bar:i')->required();
    input('get.name')->required()->setTitle('名字');
})
```

#### 参数说明

`'get.foo:i'` 中的类型转换`i`为整型，其它类型为：

Name | Type
--- | ---
i | int
s | string
b | bool
a | array
f | float
