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

## 使用

```php
use Kriss\MultiProcess\MultiProcess;
use Kriss\MultiProcess\PendingProcess;
use Symfony\Component\Process\Process;

$results = MultiProcess::create()
    ->add('hostname')
    ->add(new Process(['ls']))
    ->add(Process::fromShellCommandline('echo 123'))
    ->add(
        PendingProcess::fromShellCommandline('pwd')
            ->setStartCallback(function ($type, $buffer) {
                echo $buffer;
            })
    )
    ->wait();

var_dump($results->getOutputs());
```

其他详见 [tests](./tests)

## 参考

[illuminate/process](https://github.com/illuminate/process)

[spatie/async](https://github.com/spatie/async)