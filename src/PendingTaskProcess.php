<?php

namespace Kriss\MultiProcess;

use Closure;
use Kriss\MultiProcess\SymfonyConsole\Commands\TaskCallCommand;
use Kriss\MultiProcess\SymfonyConsole\Helper\TaskHelper;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;

class PendingTaskProcess extends PendingProcess
{
    public static ?string $globalPhpBinary = null;
    public static ?string $globalConsoleFile = null;

    protected string $phpBinary;
    protected string $consoleFile;
    /** @var array|Closure */
    protected $task;

    public function __construct()
    {
        if (!static::$globalPhpBinary) {
            static::$globalPhpBinary = (new PhpExecutableFinder())->find() ?: 'php';
        }
        $this->phpBinary = static::$globalPhpBinary;

        if (!static::$globalConsoleFile) {
            static::$globalConsoleFile = dirname(__DIR__) . '/bin/console';
        }
        $this->consoleFile = static::$globalConsoleFile;
    }

    /**
     * @param array|Closure $task
     * @return static
     */
    public static function createFromTask($task)
    {
        return static::create()->setTask($task);
    }

    /**
     * @inheritDoc
     */
    public function toSymfonyProcess(): Process
    {
        $this->setCommand([$this->phpBinary, $this->consoleFile, TaskCallCommand::COMMAND_NAME, TaskHelper::encode($this->getTask())]);

        return parent::toSymfonyProcess();
    }

    /**
     * @return string
     */
    public function getPhpBinary(): string
    {
        return $this->phpBinary;
    }

    /**
     * @param string $phpBinary
     * @return PendingTaskProcess
     */
    public function setPhpBinary(string $phpBinary): PendingTaskProcess
    {
        $this->phpBinary = $phpBinary;
        return $this;
    }

    /**
     * @return string
     */
    public function getConsoleFile(): string
    {
        return $this->consoleFile;
    }

    /**
     * @param string $consoleFile
     * @return PendingTaskProcess
     */
    public function setConsoleFile(string $consoleFile): PendingTaskProcess
    {
        $this->consoleFile = $consoleFile;
        return $this;
    }

    /**
     * @return array|Closure
     */
    public function getTask()
    {
        return $this->task;
    }

    /**
     * @param array|Closure $task
     * @return PendingTaskProcess
     */
    public function setTask($task)
    {
        $this->task = $task;
        return $this;
    }
}