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
     * @var array
     */
    protected $queue = [];
    /**
     * @var array|Process[]
     */
    protected $inProcess = [];

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
     * @param Process|string $process
     * @param callable|null $startCallback
     * @return $this
     */
    public function add($process, ?callable $startCallback = null): self
    {
        if (!$process instanceof Process) {
            $process = Process::fromShellCommandline($process);
        }

        $this->queue[] = [$process, $startCallback];
        return $this;
    }

    /**
     * 启动并等待所有进程执行完成
     * @return void
     */
    public function wait(): void
    {
        foreach ($this->queue as $item) {
            $this->startOneProcess(...$item);
        }

        $this->waitAllProcess();
        unset($this->queue);
    }

    /**
     * 启动一个进程
     * @param Process $process
     * @param callable|null $callback
     * @return void
     */
    protected function startOneProcess(Process $process, ?callable $callback = null): void
    {
        while (!$this->canStartNextProcess()) {
            if ($this->checkWaitMicroseconds > 0) {
                usleep($this->checkWaitMicroseconds);
            }
        }

        $process->start($callback);
        $pid = $process->getPid();
        if (!$pid) {
            return;
        }
        $this->inProcess[$pid] = $process;
        $this->log('start process: ' . $pid, 'debug');
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
        foreach ($this->inProcess as $pid => $process) {
            if ($this->checkAndReleaseOverProcess($pid, $process)) {
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
        foreach ($this->inProcess as $pid => $process) {
            $this->checkAndReleaseOverProcess($pid, $process);
        }

        if ($this->checkWaitMicroseconds > 0) {
            usleep($this->checkWaitMicroseconds);
        }

        $this->waitAllProcess();
    }

    /**
     * 检查并释放已经完成的进程
     * @param int $pid
     * @param Process $process
     * @return bool
     */
    protected function checkAndReleaseOverProcess(int $pid, Process $process): bool
    {
        if ($process->isRunning()) {
            return false;
        }

        $this->log("{$pid} over", 'debug');
        unset($this->inProcess[$pid]);
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
