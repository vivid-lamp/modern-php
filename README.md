# 第一章 PHP 之道
## 1.0 介绍
PHP 之道是介绍最新的 PHP 最佳实践的网站，其网址是 <https://phptherightway.com/>.  

中文翻译 1：<http://phptherightway.p2hp.com/>  
中文翻译 2：<https://learnku.com/docs/php-the-right-way/>

本章介绍 PHP 之道中值得关注的概念。

## 1.1 Xdebug
受制于时间、精力以及作者的水平，本节只介绍 vscode + Xdebug3 的断点调试。

### 1.1.1 Xdebug 配置
安装好 Xdebug3 后，调整关键配置如下:
```ini
zend_extension = xdebug.so
[XDebug]
; debug 模式可以断点调试
xdebug.mode = debug
; Xdebug 将连接此 IP 进行调试
xdebug.client_host = 127.0.0.1
; 9003 是默认端口
xdebug.client_port = 9003
xdebug.idekey = aa,bb
```

### 1.1.2 vscode 配置

在 vscode 中安装 PHP Debug 插件，点击左侧调试图标，编辑 launch.json 文件，configurations 属性的配置如下：
```json
{ 
  "configurations": [
    {
      "name": "Listen for Xdebug",
      "type": "php",
      "request": "launch",
      "port": 9003,
      "pathMappings": {
        "~/www": "${workspaceFolder}"
      }
    },
    {
      "name": "Launch currently open script",
      "type": "php",
      "request": "launch",
      "program": "${file}",
      "cwd": "${fileDirname}",
      "port": 0,
      "runtimeArgs": [
        "-dxdebug.start_with_request=yes"
      ],
      "externalConsole": true,
      "env": {
        "XDEBUG_MODE": "debug,develop",
        "XDEBUG_CONFIG": "client_port=${port}"
      }
    }
  ]
}
```
configurations 属性是一个数组，数组的第 0 个元素用来调试 cgi（包括 cli-server) 程序，第 1 个元素用来调试 cli 程序。数组元素的 port 属性是插件监听 Xdebug 的端口，pathMapping 属性是远程文件和本地文件的映射，本地调试无需配置 pathMapping。



### 1.1.3 验证
上述内容配置好之后，在 vscode 中打好断点。  
- **cgi 程序**：启动你的 PHP web 站点，在 vscode 中选择 Listen for Xdebug 启动监听，然后在浏览器 / postman 中访问你的站点，就可以断点调试了。访问时须在 Query String 或 FormData 或 COOKIE 中带上 XDEBUG_SESSION=aa (or bb) 的参数。
- **cli 程序**：在 vscode 中打开需要调试的 PHP 脚本，选择 Launch currently open script 点击 start 按钮就可以运行程序并调试了。

本节参考链接：  
vscode 的 PHP Debug 插件 <https://marketplace.visualstudio.com/items?itemName=felixfbecker.php-debug>  
PHP 的 Xdebug 扩展文档 <https://xdebug.org/docs/step_debug>


## 1.2 依赖注入
PHP 之道中对依赖注入的解释 <https://learnku.com/docs/php-the-right-way/PHP8.0/dependency_injection/11464>


## 1.3 错误和异常
### 1.3.1 错误
- 在 PHP7 中，大多数错误被作为 Error 异常抛出，和异常是同样的处理逻辑，这些错误发生时不会触发 set_error_handler() 函数。其余错误可以用 set_error_handler() 接收，接收后我们可以手动抛出一个 ErrorException。
- A.php 脚本中出现的编译错误无法在 A.php 中捕获。但是， A.php 通过 include/require 载入的文件中出现了编译错误，可以在 A.php 脚本捕获。

错误参考链接：  
PHP 文档 <https://www.php.net/manual/zh/language.errors.php7.php>  
再谈 PHP 错误与异常 <https://www.zhaoyafei.cn/content.html?id=170641242245>

### 1.3.2 异常
异常比较容易处理，在合适的时机用 try-catch 块捕获即可。下面是 PHP 预定义异常和 SPL 异常，Error 和 Exception 均继承自 Throwable。
>+ Error
>    + ArithmeticError
>        + DivisionByZeroError
>    + AssertionError
>    + CompileError
>        + ParseError
>    + TypeError
>        + ArgumentCountError
>    + ValueError
>    + UnhandledMatchError
>    + FiberError
>+ Exception
>    + ErrorException
>    + LogicException
>        + BadFunctionCallException
>            + BadMethodCallException
>        + DomainException
>        + InvalidArgumentException
>        + LengthException
>        + OutOfRangeException
>    + RuntimeException
>        + OutOfBoundsException
>        + OverflowException
>        + PDOException
>        + RangeException
>        + UnderflowException
>        + UnexpectedValueException

### 1.3.3 错误和异常处理参考
综上，我们用 set_error_handler(callable $handler) 注册一个函数来接收错误，收到错误后手动抛出一个异常。如此，所有的错误和异常就可以用 try-catch 来处理了。

在程序的尽量最外层（或者最开始）调用以下代码，可以解捕获并处理绝大部分的错误和异常。在此之前发生的错误无法处理，也没必要。
```php
set_error_handler(function (
        int $errno,
        string $errstr,
        string $errfile,
        int $errline
    ): void {
        if (!(error_reporting() & $errno)) {
            return;
        }
        throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
    }
);

try {
    // 下一步的请求处理
    $response = $handler->handle($request);
} catch (Throwable $e) {
    /**
     * handleThrowable() 方法可以报告错误并生成错误时的响应对象
     */
    $response = $this->handleThrowable($e, $request);
}

restore_error_handler();

return $response;
```
以上代码参考自：  
laminas/laminas-stratigility <https://github.com/laminas/laminas-stratigility/blob/3.5.x/src/Middleware/ErrorHandler.php>

# 第二章 PHP 框架
## 2.0 常见 PHP 框架
|框架|说明|
|  ----  | ----  |
|[Laravel](https://laravel.com/)|功能强大，社区活跃|
|[ThinkPHP](https://www.thinkphp.cn/)|国内比较流行，符合国人习惯|
|[mezzio](https://docs.mezzio.dev/mezzio/)|前身是 ZendFramework|
|[symfony](https://symfony.com/)|编程哲学、方法论；其优秀的组件被其他框架广泛使用|
|[slime](https://www.slimframework.com/)|微型框架|
|[league](https://www.slimframework.com/)|组件联盟，提供了许多非常优秀的组件|
|[yii](https://www.yiichina.com/)|高性能、易于扩展|
|...|...|
## 2.1 框架的几个核心概念
### 2.1.1 中间件
  
>中间件主要用于编织从 请求(Request) 到 响应(Response) 的整个流程。我们可以通过对多个中间件的组织，让数据的流动按预定的方式进行。中间件的本质是一个洋葱模型，我们通过一个图来解释它：

> ![，](https://hyperf.wiki/2.1/zh-cn/middleware/middleware.jpg)

> 图中的顺序为按照 Middleware 1 -> Middleware 2 -> Middleware 3 的顺序组织着，我们可以注意到当中间的横线穿过 内核 即 Middleware 3 后，又回到了 Middleware 2，为一个嵌套模型，那么实际的顺序其实就是：Request -> Middleware 1 -> Middleware 2 -> Middleware 3 -> Middleware 2 -> Middleware 1 -> Response 重点放在 核心 即 Middleware 3，它是洋葱的分界点，分界点前面的部分其实都是基于 请求(Request) 进行处理，而经过了分界点时，内核 就产出了 响应(Response) 对象，也是 内核 的主要代码目标，在之后便是对 响应(Response) 进行处理了，内核 通常是由框架负责实现的，而其它的就由您来编排了。  

基于 PSR-15 的中间件实现  
参考代码：PSR-implement/PSR15/test.php

参考链接：  
PSR-15 <https://learnku.com/docs/psr/psr-15-request-handlers/1626>  
relayphp <https://relayphp.com/>  
hyperf 中间件 <https://hyperf.wiki/2.1/#/zh-cn/middleware/middleware>  
mezzio 中间件框架 <https://docs.mezzio.dev/mezzio/v3/getting-started/features/>
### 2.1.2 容器
容器，也叫做服务容器，依赖注入（DI）容器，控制反转（IOC）容器。容器的目的是解决 DI 的问题。本节为方便起见，将容器保管的对象称作 **服务**。
> 容器是帮助我们更方便地实现依赖注入的工具，但是它们通常被误用来实现反模式设计：服务定位器。把依赖注入容器作为服务定位器注入进类中，对容器的依赖性比你原想要替换的依赖性更强，而且还会让你的代码变得更不透明，最终更难进行测试。

illuminate/container 不仅提供了 PSR-11 规范的 has 和 get 方法，还提供了丰富的服务绑定、服务创建以及服务方法调用的功能。以下举例说明 illuminate/container 的用法。

+ 在注入之前，必须先绑定服务到容器。illuminate/container 的 make 方法可以自动将服务注入到建构方法：
```php
use Illuminate\Container\Container;
use PsrImplement\PSR11\Service\ApiFetch;

$container = new Container();

// 绑定类到容器
$container->singleton(ApiFetch::class);

class Foo {

    /** @var ApiFetch */
    protected $apiFetch;

    public function __construct(ApiFetch $apiFetch)
    {
        $this->apiFetch = $apiFetch;
    }

    public function api()
    {
        return $this->apiFetch->success('msg from api', ['data from api']);
    }
}

/** @var Foo */
$foo = $container->make(Foo::class);
$response = $foo->api();
```

+ illuminate/container 的 call 方法可以把服务注入到任意方法：
```php
use Illuminate\Container\Container;
use PsrImplement\PSR11\Service\ApiFetch;

$container = new Container();

// 绑定类到容器
$container->singleton(ApiFetch::class);

class Bar
{
    public function injectToMethod(ApiFetch $apiFetch)
    {
        return $apiFetch->success('msg from injectToMethod.', ['data from injectToMethod.']);
    }
}

$bar = new Bar();
/** call 方法执行 */
$response = $container->call([$bar, 'injectToMethod']);
```
注意：上述 make 和 call 方法不是 PSR-11 的内容，illuminate/container 为了方便依赖注入提供了这些方法。  

参考代码：PSR-implement/PSR11/test.php  

参考链接：  
PSR-11 说明文档 <https://learnku.com/docs/psr/psr-11-container-meta/1622>

### 2.1.3 服务提供者
有些框架将容器叫做服务管理器，服务提供者用来给服务管理器提供服务。在有的框架中，便于直接绑定的服务在配置文件中直接指定，需要执行一些特定方法才能绑定的服务，可以在服务提供者中绑定。服务提供者的本质是在一定的时机将特定的服务绑定到容器中。

下面的例子中，由于 Router 服务在创建时需要指定其配置文件的位置，因此，这里手动创建好 Router 对象，再将这个对象绑定到容器。这里将创建过程放到回调中，就可以在真正需要 Router 对象的时候才执行回调去创建它，以节省资源。

```php
public function register()
{
    $this->app->singleton(Router::class, function () {
        $router = new Router($this->app, $this->app->getRootPath() . 'App/routes.php');
        return $router;
    });
}
```
### 2.1.4 事件机制
事件机制是观察者模式的一种实现，其关键概念如下：
>+ **事件** - 事件是发射器生成的消息。它可以是任意的 PHP 对象。
>+ **监听器** - 一个监听器是任意的可调用的 PHP 类或函数，它期待着事件的传递。相同的事件可以传递给零个或多个监听器。如果有必要，一个监听器可以入队一些其他的异步行为。
>+ **发射器** - 发射器是期待分发事件的任何 PHP 代码，也叫调用代码。它不是由任何特定的数据结构表示的，而是指用例。
>+ **分发器** - 分发器是一个服务对象，它的事件对象由发射器提供。分发器负责将事件传递给所有相关的监听器，但是必须把确定哪些监听器应该响应事件这一步骤委托给监听器提供者去做。
>+ **监听器提供者** - 监听器提供者负责确定哪些监听器是与给定事件相关的，但是它不能调用监听器。一个监听器提供者可能会指定零个或多个相关的监听器。

基于 PSR-14 的事件机制  
参考代码：PSR-implement/PSR14/test.php

## 2.2 尝试自己写一个简单框架
```bash
composer create-project vivid-lamp/installer vivid-skeleton
```

## 2.3 尝试用中间件思想组织一个框架
```bash
composer create-project vivid-lamp/pipe-skeleton
```
   
# 第三章 异步与协程

## 3.0 传统 php-fpm 的问题
+ 一个进程服务一个用户，并发数量取决于进程数量
+ 同步阻塞
+ 创建一切，销毁一切
## 3.1 异步编程
### 3.1.1 IO 复用
>目前常见的IO多路复用方案有select、poll、epoll、kqueue.
>+ select 是 *NIX 出现较早的 IO 复用方案，有较大缺陷
>+ poll 是 select 的升级版，但依然属于新瓶旧酒
>+ epoll 是 *NIX 下终极解决方案，而 kqueu 则是 Mac、BSD 下同级别的方案
### 3.1.2 ext-event
libevent For PHP  
参考代码  
+ HTTP Server：asynchronous/0-stream-event.php  
+ HTTP Client：asynchronous/1-http-client.php
### 3.1.3 Yield 与协程
#### yield 生成器
需要理解清楚 yield 生成器的进进出出。
```php
<?php
$generation = (function () {
    $v1 = yield 'a';
    echo $v1, PHP_EOL;
    $v2 = yield 'b';
    echo $v2, PHP_EOL;
    $v3 = yield 'c';
    echo $v3, PHP_EOL;
})();


echo $generation->current(), PHP_EOL;

echo $generation->send(1), PHP_EOL;
echo $generation->send(2), PHP_EOL;
$generation->send(3);

// 执行结果：
// a
// 1
// b
// 2
// c
// 3
```
从生成器往外返出 3 个值，分别是 a, b, c。从外面往生成器发送了三个值分别是 0, 1, 2。
#### 参考代码
+ yield 示例：asynchronous/2-yield.php
+ yield 协程 HTTP Client：asynchronous/3-http-client-yield.php
### 3.1.4 Fiber 纤程
参考代码  
asynchronous/4-http-client-fiber.php  

本章参考链接：  
老李秀网络编程系列博客 <https://mp.weixin.qq.com/mp/appmsgalbum?__biz=MzU4MjgzNzk5MA==&action=getalbum&album_id=1555216066405023744&scene=173&from_msgid=2247484575&from_itemidx=1&count=3&nolastread=1#wechat_redirect>  
风雪之隅 <https://www.laruence.com/2015/05/28/3038.html>  
PHP ext-event <https://www.php.net/manual/zh/class.event.php>  
ReactPHP <https://reactphp.org/>  
amp <https://amphp.org/>  
Javascript 异步编程的 4 种方法 <https://www.ruanyifeng.com/blog/2012/12/asynchronous%EF%BC%BFjavascript.html>
