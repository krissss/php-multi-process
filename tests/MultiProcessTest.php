<?php

use Kriss\MultiProcess\MultiProcess;
use Kriss\MultiProcess\PendingProcess;
use Symfony\Component\Process\Process;

it('test MultiProcess', function () {
    $startTime = microtime(true);

    MultiProcess::create()
        ->add('sleep 1')
        ->add(new Process(['sleep 1']))
        ->add(Process::fromShellCommandline('sleep 1'))
        ->add(PendingProcess::fromShellCommandline('sleep 1'))
        ->wait();

    $useTime = round(microtime(true) - $startTime, 6);
    $this->assertGreaterThan(1, $useTime);
    $this->assertLessThan(3, $useTime);
});

it('test MultiProcess config', function () {
    $startTime = microtime(true);

    MultiProcess::create([
        'logger' => new \Psr\Log\NullLogger(),
        'maxProcessCount' => 2,
        'checkWaitMicroseconds' => 0,
    ])
        ->add('sleep 1')
        ->add(new Process(['sleep 1']))
        ->add(Process::fromShellCommandline('sleep 1'))
        ->add(PendingProcess::fromShellCommandline('sleep 1'))
        ->wait();

    $useTime = round(microtime(true) - $startTime, 6);
    $this->assertGreaterThan(2, $useTime);
    $this->assertLessThan(4, $useTime);
});

it('test MultiProcess add error', function () {
    try {
        MultiProcess::create()
            ->add(['sleep', '1'])
            ->wait();
    } catch (Throwable $e) {
        $this->assertTrue($e instanceof InvalidArgumentException);
    }
});

it('test MultiProcess addMulti', function () {
    $results = MultiProcess::create()
        ->addMulti([
            'custom_name' => 'hostname',
            'custom_name2' => 'hostname',
        ])
        ->wait();

    $results2 = MultiProcess::create()
        ->add('hostname', 'custom_name')
        ->add('hostname', 'custom_name2')
        ->wait();

    $this->assertEquals($results->getOutputs(), $results2->getOutputs());
});

it('test PendingProcess', function () {
    $outputs = [];
    MultiProcess::create()
        ->add(
            PendingProcess::fromShellCommandline('hostname')
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
        ->add('hostname', 'custom_name')
        ->add(PendingProcess::fromShellCommandline('hostname'), 'custom_name2')
        ->wait();

    $hostname = gethostname();
    $this->assertIsArray($results->getProcesses());
    $this->assertInstanceOf(Process::class, $results->getProcess('custom_name'));
    $this->assertInstanceOf(PendingProcess::class, $results->getProcess('custom_name2'));
    $this->assertEquals(array_fill(0, 3, $hostname), array_values(array_map('trim', $results->getOutputs())));
    $this->assertEquals($hostname, trim($results->getOutput('custom_name')));
    $this->assertEquals('', trim($results->getOutput('not_exist_process_name')));
});
