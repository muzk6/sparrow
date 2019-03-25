# Sparrow Framework
> PHP框架 Sparrow

![](./public/app/img/logo.svg)

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
`get` | 限于 GET, OPTIONS 请求
`post` | 限于 POST, OPTIONS 请求
`auth` | 限于已登录
`csrf` | 检验`CSRF Token`，请求详情查看章节`CSRF(XSRF)`

##### 自定义中间件

```php
// \App\Core\AppMiddleware

/**
 * @param Closure $next 下一个中间件
 * @param array $context 上下文参数
 */
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

以下任意方式可开启跟踪

- CLI `php cli/trace.php --help` 命令行开启，`--help` 查看帮助
- URI `/?_xt=name0` URI开启，`name0`是当前日志的标识名
- Cookie `_xt=name0;`

*注意：`URI`, `Cookie`方式的的前提必须先设置`config/dev/whitelist.php`白名单`IP`*

---

#### 维护模式
> 开启维护模式，关闭网站访问入口

- `php cli/maintain.php`

---

#### 测试文件

- `cli/test.php` 为 `php-cli` 测试文件
- `public/test.php` 为 `php-cgi` 测试文件 

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

*注意 `\Core\AppException`, `\Core\AppPDO`, `\Core\AppMessage` 都是 `final class`，不可承继*

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

---

#### CSRF(XSRF)

`POST`请求默认校验`CSRF Token`，`GET`请求可选检验

##### 获取`Token`

使用以下任意一种方法

- `csrf()->field()`直接生成`HTML`
- `csrf()->token()`生成纯`Token`, 通过接口返回

##### 请求时带上`Token`

使用以下任意一种方法

- `POST`请求通过表单参数`_token`，后端将从`$_POST['_token']`读取
- `GET`请求通过`?_token=`，后端将从`$_GET['_token']`读取
- 通过指定请求头`X-CSRF-Token`，后端将从`$_SERVER['HTTP_X_CSRF_TOKEN']`读取

---

#### `helper` 辅助函数用例

##### `config()` 配置文件

- `config('app')`
- 假设当前环境是`dev`
- 依次搜索`config/app.php, config/dev/app.php`, 存在时返回第一个结果文件的内容，都不存在时返回`null`

##### `trans()` 多语言文本

- `trans(10001000)`
- 假设当前语言是`zh_CN`, 默认语言是`en`
- 依次搜索`lang/zh_CN.php, lang/en.php`, 存在`10001000`这个`key`时返回第一个结果内容，都不存在时返回`?`

##### `core()` 实例化核心类

- `core('AppFlash')`
- 依次搜索类`App\Core\AppFlash, Core\AppFlash`, 存在时返回第一个结果类，都不存在时返回`null`

##### `view()` 视图模板对象

- 需要安装`blade`库`composer require duncan3dc/blade`
- `view()`返回`blade`对象

---

#### 数据库查询
> `db()`支持`PDO`对象的所有方法、且自动切换主从(所有`select`连接从库)、能更方便地防注入

##### 查询1行所有列 `->selectOne()`
> 成功时返回第1行记录的数组

```php
// select * from table0 limit 1
db()->table('table0')->selectOne(null);

// select * from table0 where id = 1 limit 1
db()->table('table0')->selectOne('id=1'); // 有注入风险
db()->table('table0')->selectOne(['id=1']); // 有注入风险
db()->table('table0')->selectOne(['id=?', 1]); // 防注入
db()->table('table0')->selectOne(['id=?', [1]]); // 防注入
db()->table('table0')->selectOne(['id=:id', ['id' => 1]]); // 防注入
db()->table('table0')->selectOne(['id=:id', [':id' => 1]]); // 防注入
```

##### 查询1行1列 `->selectColumn()`
> 成功时返回第1行第1列的值<br>
第二个参数`where`与`selectOne()`的`where`用法一样

```php
// select col1 from table0 limit 1
db()->table('table0')->selectColumn('col1', null);
db()->table('table0')->selectColumn(['col1'], null);

// select COUNT(1) from table0 limit 1
db()->table('table0')->selectColumn(['expr' => 'COUNT(1)'], null);
```

##### 查询多行 `->selectColumn()`
> 成功时返回所有行的记录数组<br>
第二个参数`where`与`->selectOne()`的`where`用法一样

```php
// select col1, col2 from table0
db()->table('table0')->selectColumn('col1, col2', null);
db()->table('table0')->selectColumn(['col1', 'col2'], null);

// select col1, COUNT(1) from table0
db()->table('table0')->selectColumn(['col1', ['expr' => 'COUNT(1)']], null);
```

##### 综合查询

```php
// select * from table0 where id > 100 order by col0 desc limit 0, 10 
db()->table('table0')->append('order by col0 desc')->limit(10)->selectAll('*', ['id>?', 100]);

// select col0, col1 from table0 where name like 'tom%' group by col0 limit 0, 10 
db()->table('table0')->append('group by col0')->page(1, 10)->selectAll('col0, col1', ['name like :name', ['name' => 'tom%']]);

// select count(1) from table0
db()->table('table0')->count(null);
```

- `append('order by col0 desc')`把自己的`sql`(还支持`group by, having`等等)拼接到`where`语句后面
- `limit(10)`等价于`limit([10]), limit(0, 10), limit([0, 10]), page(1, 10)`

##### 插入 `->insert()`

```php
// insert into table0(col0) values(1)
db()->table('table0')->insert(['col0' => 1]);

// insert into table0(col0) values(1),(2)
db()->table('table0')->insert([ ['col0' => 1], ['col0' => 2] ]);

// insert into table0(ctime) values(UNIX_TIMESTAMP())
db()->table('table0')->insert(['ctime' => ['expr' => 'UNIX_TIMESTAMP()']]);
```

以下两个的用法与`->insert()`一致

- `->insertIgnore()` 即 `insert ignore ...`
- `->replace()` 即 `replace into ...`

##### 插入更新 `->insertUpdate()`

```php
// insert into table0(col0) values(1) on duplicate key update num = num + 1 
db()->table('table0')->insertUpdate(['col0' => 1], ['num' => ['expr' => 'num + 1']]);

// insert into table0(col0) values(1) on duplicate key update utime = UNIX_TIMESTAMP()
db()->table('table0')->insertUpdate(['col0' => 1], ['utime' => ['expr' => 'UNIX_TIMESTAMP()']);
```

##### 更新 `->update()`
> 第二个参数`where`与`selectOne()`的`where`用法一样

```php
// update table0 set col0 = 1 where id = 10
db()->table('table0')->update(['col0' => 1], ['id=?', 10]);

// update table0 set num = num + 1 where id = 10
db()->table('table0')->update(['num' => ['expr' => 'num + 1']], ['id=?', 10]);

// update table0 set utime = UNIX_TIMESTAMP() where id = 10
db()->table('table0')->update(['utime' => ['expr' => 'UNIX_TIMESTAMP()']], ['id=?', 10]);
```

##### 删除 `->delete()`
> 参数`where`与`selectOne()`的`where`用法一样

```php
// delete from table0 where id = 10
db()->table('table0')->delete(['id=?', 10]);
```

##### 上一次查询的影响行数 `->affectedRows()`
> 在主库查询影响行数

```php
// select row_count()
db()->affectedRows();
```

*另外`->lastInsertId()`也会自动切换到主库查询上次插入的`id`*

##### 下一次强制使用主库 `->forceMaster()`
> 一般用于`select`语句，因为非`select`都已默认是主库

```php
// 在主库查询 select * from table0 limit 1
db()->forceMaster()->table('table0')->selectOne(['id=?', 1]);
```

##### 一次性切换分区 `->section()`
> 相关配置在`config/.../database.php`的`sections`里配置

```php
// 切换到分区 sec0
db()->section('sec0');
```
