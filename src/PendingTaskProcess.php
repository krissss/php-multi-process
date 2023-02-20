<?php

namespace Kriss\MultiProcess;

use Closure;
use Kriss\MultiProcess\SymfonyConsole\Commands\TaskCallCommand;
use Kriss\MultiProcess\SymfonyConsole\Helper\TaskHelper;
use Symfony\Component\Process\Process;

class PendingTaskProcess extends PendingProcess
{
    /** @var array|Closure */
    protected $task;

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
        $this->setCommand([PHP_BINARY, dirname(__DIR__) . '/bin/console', TaskCallCommand::COMMAND_NAME, TaskHelper::encode($this->getTask())]);

        return parent::toSymfonyProcess();
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