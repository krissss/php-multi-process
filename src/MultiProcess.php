<?php

namespace Kriss\MultiProcess;

use Psr\Log\LoggerInterface;
use Symfony\Component\Process\Process;

class MultiProcess
{
    /**
     * 日志
     * @var LoggerInterface|null
     */
    protected $logger = null;
    /**
     * 最大进程数，为 0 时不限制
     * @var int
     */
    protected $maxProcessCount = 0;
    /**
     * 检查最大进程数的间隔时间（毫秒），为 0 时不等待
     * @var int
     */
    protected $checkWaitMicroseconds = 0;

    /**
     * @var array|PendingProcess[]|Process[]
     */
    protected $queue = [];
    /**
     * @var array<string, Process>
     */
    protected $inProcess = [];
    /**
     * @var array<string, Process>
     */
    protected $results = [];

    public function __construct(array $config = [])
    {
        foreach ($config as $key => $value) {
            $this->{$key} = $value;
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
     * @param PendingProcess|Process|string $pendingProcess
     * @param string|null $name
     * @return $this
     */
    public function add($pendingProcess, string $name = null): self
    {
        if (is_string($pendingProcess)) {
            $pendingProcess = PendingProcess::fromShellCommandline($pendingProcess);
        }
        if (!$pendingProcess instanceof Process) {
            throw new \InvalidArgumentException('$pendingProcess must be an Process');
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
            $this->startOneProcess(...$item);
        }

        $this->waitAllProcess();
        unset($this->queue);

        return new MultiProcessResults($this->results);
    }

    /**
     * 启动一个进程
     * @param Process $process
     * @param string|null $name
     * @return void
     */
    protected function startOneProcess(Process $process, string $name = null): void
    {
        while (!$this->canStartNextProcess()) {
            if ($this->checkWaitMicroseconds > 0) {
                usleep($this->checkWaitMicroseconds);
            }
        }

        $startCallback = $process instanceof PendingProcess ? $process->getStartCallback() : null;
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
        if ($this->maxProcessCount <= 0 || count($this->inProcess) < $this->maxProcessCount) {
            return true;
        }

        $can = false;
        foreach ($this->inProcess as $name => $process) {
            if ($this->checkAndReleaseOverProcess($name, $process)) {
                $can = true;
            }
        }

        if (!$can) {
            $this->log('too many process, wait ...', 'debug');
        }

        return $can;
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
            $this->checkAndReleaseOverProcess($name, $process);
        }

        if ($this->checkWaitMicroseconds > 0) {
            usleep($this->checkWaitMicroseconds);
        }

        $this->waitAllProcess();
    }

    /**
     * 检查并释放已经完成的进程
     * @param string $name
     * @param Process $process
     * @return bool
     */
    protected function checkAndReleaseOverProcess(string $name, Process $process): bool
    {
        if ($process->isRunning()) {
            return false;
        }

        $this->log("{$name} over", 'debug');

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
