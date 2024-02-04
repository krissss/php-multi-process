<?php

namespace Kriss\MultiProcess;

use Closure;
use Psr\Log\LoggerInterface;
use Symfony\Component\Process\Process;

class MultiProcess
{
    public static int $globalCheckWaitMicroseconds = 300;
    public static int $globalMaxProcessCount = -1; // -1 时在 cli 下取当前系统的cpu核心数，取不到取10
    public static ?LoggerInterface $defaultLogger = null;

    /**
     * 日志
     * @var LoggerInterface|null
     */
    protected ?LoggerInterface $logger = null;
    /**
     * 最大进程数，为 0 时不限制
     * @var int|null
     */
    protected ?int $maxProcessCount = null;
    /**
     * 检查最大进程数的间隔时间（毫秒）
     * @var int|null
     */
    protected ?int $checkWaitMicroseconds = null;

    /**
     * @var array|Process[]
     */
    protected array $queue = [];
    /**
     * @var array<string, Process>
     */
    protected array $inProcess = [];
    /**
     * @var array<string, Process>
     */
    protected array $results = [];

    public function __construct(array $config = [])
    {
        foreach ($config as $key => $value) {
            $this->{$key} = $value;
        }
        if ($this->logger === null && static::$defaultLogger) {
            $this->logger = static::$defaultLogger;
        }
        if ($this->checkWaitMicroseconds === null || $this->checkWaitMicroseconds <= 0) {
            // 不能不 sleep，会导致死循环(fpm下)，原因目前未知
            $this->checkWaitMicroseconds = static::$globalCheckWaitMicroseconds;
        }
        if ($this->maxProcessCount === null && static::$globalMaxProcessCount) {
            $this->maxProcessCount = static::$globalMaxProcessCount;
        }
        if ($this->maxProcessCount === -1) {
            if (PHP_SAPI === 'cli') {
                try {
                    $this->maxProcessCount = (int)shell_exec('nproc');
                } catch (\Throwable $e) {
                    $this->log('get cpu core error: ' . $e->getMessage(), 'warning');
                }
            }
            if ($this->maxProcessCount <= 0) {
                $this->maxProcessCount = 10;
            }
            $this->log('use max process count: ' . $this->maxProcessCount, 'debug');
        }
    }

    /**
     * 创建实例
     * @param array $config
     * @return static
     */
    public static function create(array $config = []): self
    {
        return new static($config);
    }

    /**
     * 添加一个即将执行的进程
     * @param mixed $pendingProcess
     * @param string|null $name
     * @return $this
     */
    public function add($pendingProcess, string $name = null): self
    {
        if (is_string($pendingProcess)) {
            $pendingProcess = Process::fromShellCommandline($pendingProcess);
        }
        if (is_array($pendingProcess) || $pendingProcess instanceof Closure) {
            $pendingProcess = PendingTaskProcess::createFromTask($pendingProcess);
        }
        if (!$pendingProcess instanceof Process && !$pendingProcess instanceof PendingProcess) {
            throw new \InvalidArgumentException('$pendingProcess type error');
        }

        $this->queue[] = [$pendingProcess, $name];
        return $this;
    }

    /**
     * 批量添加
     * @param array $pendingProcesses
     * @return $this
     */
    public function addMulti(array $pendingProcesses): self
    {
        foreach ($pendingProcesses as $name => $pendingProcess) {
            $name = is_string($name) ? $name : null;
            $this->add($pendingProcess, $name);
        }
        return $this;
    }

    /**
     * 启动并等待所有进程执行完成
     * @return MultiProcessResults
     */
    public function wait(): MultiProcessResults
    {
        foreach ($this->queue as $item) {
            while (!$this->canStartNextProcess()) {
                usleep($this->checkWaitMicroseconds);
            }

            $this->startOneProcess(...$item);
        }
        unset($this->queue);

        $this->waitAllProcess();

        return new MultiProcessResults($this->results);
    }

    /**
     * 启动一个进程
     * @param Process|PendingProcess $pendingProcess
     * @param string|null $name
     * @return void
     */
    protected function startOneProcess($pendingProcess, string $name = null): void
    {
        $process = $pendingProcess;
        $startCallback = null;
        if ($pendingProcess instanceof PendingProcess) {
            $process = $pendingProcess->toSymfonyProcess();
            $startCallback = $pendingProcess->getStartCallback();
        }

        $process->start($startCallback);

        $pid = $process->getPid();
        $name = $name ?? ('pid_' . $pid);
        $this->inProcess[$name] = $process;
        $this->log('start process: ' . $name, 'debug');
    }

    /**
     * 是否可以启动下一个进程
     * @return bool
     */
    protected function canStartNextProcess(): bool
    {
        if (
            $this->maxProcessCount <= 0 // 不限制最大执行进程数
            || count($this->inProcess) < $this->maxProcessCount // 当前在执行中的进程数未达到最大值
        ) {
            return true;
        }

        // 检查当前执行中的进程是否有结束的
        $hasFinished = false;
        foreach ($this->inProcess as $name => $process) {
            if ($this->checkAndUnsetFinishedProcess($name, $process)) {
                $hasFinished = true;
            }
        }
        if ($hasFinished) {
            return $this->canStartNextProcess();
        }

        $this->log('too many process, wait ...', 'debug');
        return false;
    }

    /**
     * 等待所有进程完成
     * @return void
     */
    protected function waitAllProcess(): void
    {
        if (count($this->inProcess) <= 0) {
            return;
        }
        foreach ($this->inProcess as $name => $process) {
            $this->checkAndUnsetFinishedProcess($name, $process);
        }

        usleep($this->checkWaitMicroseconds);

        $this->waitAllProcess();
    }

    /**
     * 检查并释放已经完成的进程
     * @param string $name
     * @param Process $process
     * @return bool
     */
    protected function checkAndUnsetFinishedProcess(string $name, Process $process): bool
    {
        if ($process->isRunning()) {
            return false;
        }

        $this->log("{$name} finished", 'debug');

        $this->results[$name] = $process;
        unset($this->inProcess[$name]);

        return true;
    }

    /**
     * log
     * @param string $msg
     * @param string $level
     * @return void
     */
    protected function log(string $msg, string $level = 'info')
    {
        if ($this->logger instanceof LoggerInterface) {
            $this->logger->log($level, $msg);
        }
    }
}
