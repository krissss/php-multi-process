<?php

namespace Kriss\MultiProcess;

use Closure;
use Kriss\MultiProcess\SymfonyConsole\Commands\TaskCallCommand;
use Kriss\MultiProcess\SymfonyConsole\Helper\TaskHelper;
use Symfony\Component\Process\Process;

class PendingTaskProcess extends PendingProcess
{
    public static ?string $globalConsoleFile = null;

    protected string $consoleFile;
    /** @var array|Closure */
    protected $task;

    public function __construct()
    {
        $this->consoleFile = static::$globalConsoleFile ?: dirname(__DIR__) . '/bin/console';
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
        $this->setCommand([PHP_BINARY, $this->consoleFile, TaskCallCommand::COMMAND_NAME, TaskHelper::encode($this->getTask())]);

        return parent::toSymfonyProcess();
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