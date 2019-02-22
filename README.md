# knf

PHP框架 KuiNiu Framework

#### 路由格式

URI | Controller | Action
--- | --- | ---
`/foo` | `FooController` | `index()`
`/foo/` | `FooController` | `index()`
`/foo/bar` | `FooController` | `bar()`
`/foo/bar/` | `FooController` | `bar()`

#### XDebug Trace
> 跟踪调试日志

以下两种方法开启跟踪
- `php cli/trace.php`
- `?xt=<value>`

#### 维护模式
> 开启维护模式，关闭网站访问入口

- `php cli/maintain.php`

#### 目录结构

Dir | Desc
--- | ---
app | 业务逻辑
app/Controllers | 控制器层，负责输入(表单验证、权限控制……)、处理(Service)、输出
app/Models | 模型层，1表1模型
app/Services | 服务层，负责业务逻辑
cli | 命令行脚本
config | 配置文件，通用配置放在当前目录下
config/dev | dev环境的配置
core | 框架核心文件
data | 缓存、日志数据目录，需要写权限
lang | 国际化多语言
public | Web入口目录
rpc | RPC入口目录，禁止对外网开放
vendor | Composer库
views | 视图文件
workers | 长驻运行的脚本