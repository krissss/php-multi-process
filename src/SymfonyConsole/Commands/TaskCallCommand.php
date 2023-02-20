<?php

namespace Kriss\MultiProcess\SymfonyConsole\Commands;

use Closure;
use Kriss\MultiProcess\SymfonyConsole\Helper\TaskHelper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TaskCallCommand extends Command
{
    public const COMMAND_NAME = 'multi-process:task-call';

    protected static $defaultDescription = '多进程动态调用入口';

    public function __construct()
    {
        parent::__construct(static::COMMAND_NAME);
    }

    protected function configure()
    {
        $this->addArgument('task', InputArgument::REQUIRED, '任务');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $task = $this->getTask($input);

        $result = call_user_func($task);

        $this->solveOutput($result, $output);

        return 0;
    }

    /**
     * @param InputInterface $input
     * @return Closure|array
     */
    protected function getTask(InputInterface $input)
    {
        $task = $input->getArgument('task');
        return TaskHelper::decode($task);
    }

    /**
     * @param mixed $result
     * @param OutputInterface $output
     * @return void
     */
    public function solveOutput($result, OutputInterface $output): void
    {
        if (is_string($result)) {
            $output->writeln($result);
        } elseif (is_null($result)) {
            $output->writeln('');
        } else {
            $output->writeln(TaskHelper::encode($result));
        }
    }
}