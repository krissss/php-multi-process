<?php

namespace Kriss\MultiProcessTests;

use Kriss\MultiProcess\MultiProcess;
use Kriss\MultiProcess\PendingProcess;
use Kriss\MultiProcess\PendingTaskProcess;
use Kriss\MultiProcess\SymfonyConsole\Helper\TaskHelper;
use Kriss\MultiProcessTests\Fixtures\CallbackClass;
use Kriss\MultiProcessTests\Fixtures\MyMultiProcess;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;

class MultiProcessTest extends TestCase
{
    protected function tearDown(): void
    {
        MultiProcess::$globalMaxProcessCount = -1;
        MultiProcess::$defaultLogger = null;
        MultiProcess::$globalCheckWaitMicroseconds = 300;

        PendingTaskProcess::$globalConsoleFile = null;
        PendingTaskProcess::$globalPhpBinary = null;
    }

    public function testAsync()
    {
        $startTime = microtime(true);

        MultiProcess::$globalMaxProcessCount = 5;

        MultiProcess::create()
            ->add('sleep 1')
            ->add('sleep 1')
            ->add('sleep 1')
            ->add('sleep 1')
            ->wait();

        $useTime = round(microtime(true) - $startTime, 6);
        $this->assertGreaterThan(1, $useTime);
        $this->assertLessThan(4, $useTime);
    }

    public function testDifferentAdd()
    {
        $results = MultiProcess::create()
            ->add('hostname')
            ->add(Process::fromShellCommandline('hostname'), 'p2')
            ->add(PendingProcess::createFromCommand('hostname'), 'p3')
            ->add([Fixtures\CallbackClass::class, 'getHostname'], 'p4')
            ->add(fn() => Fixtures\CallbackClass::getHostname(), 'p5')
            ->addMulti([
                'p6' => 'hostname',
                'p7' => fn() => CallbackClass::getHostname(),
            ])
            ->wait();

        $hostname = gethostname();
        $this->assertEquals(array_fill(0, 7, $hostname), array_values($results->getOutputs()));
        $this->assertEquals('p3', array_keys($results->getProcesses())[2]);
        $this->assertEquals('p7', array_keys($results->getProcesses())[6]);
        $this->assertStringStartsWith('pid_', array_keys($results->getProcesses())[0]);

        try {
            MultiProcess::create()
                ->add(new \stdClass())
                ->wait();
        } catch (\Throwable $e) {
            $this->assertStringContainsString('$pendingProcess type error', $e->getMessage());
        }
    }

    public function testResult()
    {
        $results = MultiProcess::create()
            ->add('hostname', 'p1')
            ->add(function () {
                throw new \Exception('存在异常');
            }, 'p2')
            ->wait();

        $hostname = gethostname();

        $this->assertInstanceOf(Process::class, $results->getProcess('p1'));
        $this->assertCount(2, $results->getProcesses());
        $this->assertCount(2, $results->getOutputs());
        $this->assertCount(2, $results->getErrorOutputs());
        $this->assertEquals($hostname, $results->getOutput('p1'));
        $this->assertEquals($hostname . PHP_EOL, $results->getProcess('p1')->getOutput());

        $this->assertTrue($results->getIsSuccess('p1'));
        $this->assertFalse($results->getIsSuccess('p2'));
        $this->assertFalse($results->getIsAllSuccess());

        $this->assertEquals('', $results->getOutput('notExist'));
        $this->assertEquals('', $results->getErrorOutput('notExist'));
        $this->assertEquals(null, $results->getProcess('notExist'));
        $this->assertEquals(false, $results->getIsSuccess('notExist', false));

        $this->assertStringContainsString('存在异常', $results->getErrorOutput('p2'));
        $this->assertEquals(['p2'], $results->getFailedNames());

        $results = MultiProcess::create()->wait();
        $this->assertTrue($results->getIsAllSuccess());
    }

    public function testConfigMaxProcessCount()
    {
        MultiProcess::$globalMaxProcessCount = 2;

        $startTime = microtime(true);
        MultiProcess::create()
            ->addMulti([
                'sleep 1',
                'sleep 1',
                'sleep 1',
            ])
            ->wait();
        $useTime = round(microtime(true) - $startTime, 6);
        $this->assertGreaterThan(2, $useTime);
        $this->assertLessThan(4, $useTime);

        $startTime = microtime(true);
        MultiProcess::$globalMaxProcessCount = 4;
        MultiProcess::create(['maxProcessCount' => 2])
            ->addMulti([
                'sleep 1',
                'sleep 1',
                'sleep 1',
            ])
            ->wait();
        $useTime = round(microtime(true) - $startTime, 6);
        $this->assertGreaterThan(2, $useTime);
        $this->assertLessThan(4, $useTime);
    }

    public function testConfigLogger()
    {
        MultiProcess::$defaultLogger = new NullLogger();

        $results = MultiProcess::create()
            ->add('hostname', 'p1')
            ->wait();

        $this->assertEquals(gethostname(), $results->getOutput('p1'));
    }

    public function testPendingProcess()
    {
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

        $pendingProcess = PendingProcess::createFromCommand('hostname')
            ->setTimeout(10)
            ->setIdleTimeout(300)
            ->setInput('my-input')
            ->setCommand('hostname2')
            ->setCwd(__DIR__)
            ->setEnv(['env=1'])
            ->setOptions(['blocking_pipes' => 1]);
        $this->assertEquals(10, $pendingProcess->getTimeout());
        $this->assertEquals(300, $pendingProcess->getIdleTimeout());
        $this->assertEquals('my-input', $pendingProcess->getInput());
        $this->assertEquals('hostname2', $pendingProcess->getCommand());
        $this->assertEquals(__DIR__, $pendingProcess->getCwd());
        $this->assertEquals(['env=1'], $pendingProcess->getEnv());
        $this->assertEquals(['blocking_pipes' => 1], $pendingProcess->getOptions());

        $process = $pendingProcess->toSymfonyProcess();
        $this->assertEquals(10, $process->getTimeout());
        $this->assertEquals(300, $process->getIdleTimeout());
        $this->assertEquals('my-input', $process->getInput());
        $this->assertEquals('hostname2', $process->getCommandLine());
        $this->assertEquals(__DIR__, $process->getWorkingDirectory());
        $this->assertEquals(['env=1'], $process->getEnv());

        // Output cannot be disabled while an idle timeout is set.
        $pendingProcess = PendingProcess::createFromCommand('hostname')
            ->setQuietly();
        $this->assertEquals(true, $pendingProcess->isQuietly());
        $process = $pendingProcess->toSymfonyProcess();
        $this->assertEquals(true, $process->isOutputDisabled());
    }

    public function testTask()
    {
        $hostname = gethostname();
        $self = new CallbackClass();
        $results = MultiProcess::create()
            // 数组形式的静态调用
            ->add([CallbackClass::class, 'getHostname'], 'p1')
            // Closure
            ->add(fn() => CallbackClass::getHostname(), 'p2')
            // Closure 支持 use
            ->add(fn() => CallbackClass::getValue($hostname), 'p3')
            // 数组形式的对象调用
            ->add([$self, 'getHostname2'], 'p4')
            // Closure 支持 $this
            ->add(fn() => $self->getHostname2(), 'p5')
            // 返回数组形式
            ->add(fn() => [1, 2], 'p6')
            // 返回对象
            ->add(fn() => $self, 'p7')
            // 使用 PendingTaskProcess
            ->add(PendingTaskProcess::createFromTask(fn() => CallbackClass::getHostname()), 'p8')
            ->wait();

        $outputs = array_values($results->getOutputs());
        $this->assertEquals(array_fill(0, 5, $hostname), array_splice($outputs, 0, 5));
        $this->assertEquals([1, 2], TaskHelper::decode($results->getOutput('p6')));
        $this->assertInstanceOf(CallbackClass::class, TaskHelper::decode($results->getOutput('p7')));
        /** @var CallbackClass $obj */
        $obj = TaskHelper::decode($results->getOutput('p7'));
        $this->assertInstanceOf(CallbackClass::class, $obj);
        $this->assertEquals($hostname, $obj->getHostname2());
        $this->assertEquals($hostname, $results->getOutput('p8'));
    }

    public function testConfigPhpBinary()
    {
        $php = PendingTaskProcess::createFromTask(fn() => 'ok')->getPhpBinary();
        $this->assertEquals((new PhpExecutableFinder())->find() ?: 'php', $php);

        PendingTaskProcess::$globalPhpBinary = 'myPHP';
        $php = PendingTaskProcess::createFromTask(fn() => 'ok')->getPhpBinary();
        $this->assertEquals('myPHP', $php);

        $php = PendingTaskProcess::createFromTask(fn() => 'ok')->getPhpBinary();
        $this->assertEquals('myPHP', $php);

        $php = PendingTaskProcess::createFromTask(fn() => 'ok')->setPhpBinary('myNextPHP')->getPhpBinary();
        $this->assertEquals('myNextPHP', $php);
    }

    public function testConfigConsoleFile()
    {
        PendingTaskProcess::$globalConsoleFile = __DIR__ . '/Fixtures/my-console';

        $results = MultiProcess::create()
            ->add(fn() => 'ok', 'p1')
            ->wait();

        $output = $results->getOutput('p1');
        $this->assertEquals('my task' . PHP_EOL . 'ok', $output);

        PendingTaskProcess::$globalConsoleFile = 'not-exist';
        $results = MultiProcess::create()
            ->add(
                PendingTaskProcess::createFromTask(fn() => 'ok')
                    ->setConsoleFile(__DIR__ . '/Fixtures/my-console'),
                'p1'
            )
            ->wait();

        $output = $results->getOutput('p1');
        $this->assertEquals('my task' . PHP_EOL . 'ok', $output);

        $this->assertEquals('not-exist', PendingTaskProcess::createFromTask(fn() => 'ok')->getConsoleFile());
    }

    public function testAutoMaxProcessCount()
    {
        MyMultiProcess::$globalMaxProcessCount = -1;

        $mp = MyMultiProcess::create();
        try {
            $count = (int)shell_exec('nproc');
        } catch (\Throwable $e) {
        }
        if ($count <= 0) {
            $count = 10;
        }

        $this->assertEquals($count, $mp->getMaxProcessCount());
    }
}
