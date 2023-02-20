<?php

use Kriss\MultiProcess\MultiProcess;
use Kriss\MultiProcess\PendingProcess;
use Kriss\MultiProcess\SymfonyConsole\Helper\TaskHelper;
use Kriss\MultiProcessTests\SymfonyConsoleTestClass;
use Symfony\Component\Process\Process;

it('test MultiProcess async', function () {
    $startTime = microtime(true);

    MultiProcess::create()
        ->add('sleep 1')
        ->add('sleep 1')
        ->add('sleep 1')
        ->add('sleep 1')
        ->wait();

    $useTime = round(microtime(true) - $startTime, 6);
    $this->assertGreaterThan(1, $useTime);
    $this->assertLessThan(4, $useTime);
});

it('test MultiProcess config', function () {
    $startTime = microtime(true);

    MultiProcess::create([
        'logger' => new \Psr\Log\NullLogger(),
        'maxProcessCount' => 2,
        'checkWaitMicroseconds' => 0,
    ])
        ->add('sleep 1')
        ->add(Process::fromShellCommandline('sleep 1'))
        ->add(PendingProcess::createFromCommand(['sleep', 1]))
        ->wait();

    $useTime = round(microtime(true) - $startTime, 6);
    $this->assertGreaterThan(2, $useTime);
    $this->assertLessThan(4, $useTime);
});

it('test MultiProcess add error', function () {
    try {
        MultiProcess::create()
            ->add(new class {})
            ->wait();
    } catch (Throwable $e) {
        $this->assertTrue($e instanceof InvalidArgumentException);
    }
});

it('test MultiProcess addMulti', function () {
    $results = MultiProcess::create()
        ->addMulti([
            'p1' => 'hostname',
            'p2' => PendingProcess::createFromCommand('hostname'),
        ])
        ->wait();

    $results2 = MultiProcess::create()
        ->add('hostname', 'p1')
        ->add('hostname', 'p2')
        ->wait();

    $this->assertEquals($results->getOutputs(), $results2->getOutputs());
});

it('test PendingProcess', function () {
    $outputs = [];
    MultiProcess::create()
        ->add(
            PendingProcess::createFromCommand('hostname')
                ->setStartCallback(function ($type, $buffer) use (&$outputs) {
                    $outputs = [$type, $buffer];
                })
        )
        ->wait();

    $this->assertEquals(Process::OUT, $outputs[0]);
    $this->assertEquals(gethostname(), trim($outputs[1]));
});

it('test MultiProcessResult', function () {
    $results = MultiProcess::create()
        ->add('hostname')
        ->add('hostname', 'p1')
        ->add(PendingProcess::createFromCommand('hostname'), 'p2')
        ->wait();

    $hostname = gethostname();
    $this->assertIsArray($results->getProcesses());
    $this->assertInstanceOf(Process::class, $results->getProcess('p1'));
    $this->assertInstanceOf(Process::class, $results->getProcess('p2'));
    $this->assertEquals(array_fill(0, 3, $hostname), array_values($results->getOutputs()));
    $this->assertEquals($hostname, $results->getOutput('p1'));
    $this->assertEquals('', $results->getOutput('not_exist_process_name'));
});

it('test MultiProcess Task', function () {
    // 问题：不能用于 覆盖 测试
    // pest 环境下无法正确序列化 Closure，所以需要放到正确的 class
    $results = SymfonyConsoleTestClass::makeResults();

    $this->assertEquals('ok', $results->getOutput('p1'));
    $this->assertEquals('ok', $results->getOutput('p2'));
    $this->assertEquals('new ok', $results->getOutput('p3'));
    $this->assertEquals('user', $results->getOutput('p4'));
    $this->assertEquals('user', $results->getOutput('p5'));

    $result = $results->getOutput('p6');
    $this->assertEquals([1, 2], TaskHelper::decode($result));

    $result = $results->getOutput('p7');
    $obj = TaskHelper::decode($result);
    $this->assertInstanceOf(SymfonyConsoleTestClass::class, $obj);
    /** @var SymfonyConsoleTestClass $obj */
    $this->assertEquals('user', $obj->getUser());

    $this->assertEquals('ok', $results->getOutput('p8'));
});
