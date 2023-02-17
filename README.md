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
- 支持调用系统命令和 php 代码

## 使用

基本使用

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

其他详见 [tests](./tests)

## 参考

[illuminate/process](https://github.com/illuminate/process)

[spatie/async](https://github.com/spatie/async)