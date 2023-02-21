# php Multi Process

基于 [symfony/process](https://github.com/symfony/process) 实现的多进程管理

## 安装

```bash
composer require kriss/multi-process
```

## 特性

- 异步
- 并行
- 支持控制最大进程数
- 支持 logger
- 支持控制进程检查频率
- 获取进程最终输出
- 同步获取进程输出
- 支持 cli 和 fpm
- 支持调用系统命令和 php 代码（支持调用第三方框架自带的命令行入口，如 artisan）

## 基本使用

```php
use Kriss\MultiProcess\MultiProcess;
use Kriss\MultiProcess\PendingProcess;
use Symfony\Component\Process\Process;

// 调用系统命令
$results = MultiProcess::create()
    ->add('hostname')
    ->add(new Process(['ls']))
    ->add(Process::fromShellCommandline('echo 123'))
    ->add(
        PendingProcess::fromShellCommandline('pwd')
            ->setStartCallback(function ($type, $buffer) {
                echo $buffer;
            })
    );
    
var_dump($results->getOutputs());

// 调用 PHP 代码
$value = 123;
$results = MultiProcess::create()
    // callback
    ->add(function () use ($value) {
        // 支持 use 和 $this 使用
        return $value;
    })
    // 调用 SomeClass::method
    //->add([SomeClass::class, 'method'])
    ->wait();
    
var_dump($results->getOutputs());
```

## 如何调用框架自带的命令行

**注意**：此方法仅支持 `symfony/console` 类型的命令行

> 已自动支持 laravel 和 webman，无需进行以下配置

一般框架都是有配置的，如果使用当前扩展自带的 `bin/console` 无法加载配置相关代码，因此无法做到很多框架中所谓的 `Kernel::start`，就没法使用相关的组件，
解决此问题只需要进行如下配置即可

1. 在框架 `startup` 或 `bootstrap`（如 Laravel 的 AppServiceProvider 的 boot 方法中），添加 `PendingTaskProcess::$globalConsoleFile = __DIR__ . '/path/to/console';`
2. 注入 `TaskCallCommand` 命令（如 Laravel 的 AppServiceProvider 的 register 方法中添加 `$this->commands(TaskCallCommand::class)`）

## 与单进程 PHP 处理上的区别

可以使用以下方式对比测试使用该扩展和php原生循环的时间区别

```php
use Kriss\MultiProcess\MultiProcess;

$filename = 'https://www.example.com/';

$startTime = microtime(true);
for ($i = 0; $i < 3; $i++) {
    file_get_contents($filename);
}
echo ('use time: ' . round(microtime(true) - $startTime, 6)) . PHP_EOL; // 3秒以上

for ($i = 0; $i < 3; $i++) {
    $processes[] = PendingProcess::fromShellCommandline("php -r \"echo file_get_contents('$filename');\"");
}
$startTime = microtime(true);
MultiProcess::create()
    ->addMulti($processes)
    ->wait();
echo ('use time: ' . round(microtime(true) - $startTime, 6)) . PHP_EOL; // 1秒多
```

## 参考

其他示例详见 [tests](./tests)

[illuminate/process](https://github.com/illuminate/process)

[spatie/async](https://github.com/spatie/async)