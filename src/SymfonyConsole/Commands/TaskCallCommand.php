<?php

namespace Kriss\MultiProcess\SymfonyConsole\Commands;

use Kriss\MultiProcess\SymfonyConsole\Helper\TaskHelper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TaskCallCommand extends Command
{
    public const COMMAND_NAME = 'multi-process:task-call';

    protected static $defaultName = self::COMMAND_NAME;
    protected static $defaultDescription = '多进程动态调用入口';

    protected function configure()
    {
        $this->addArgument('task', InputArgument::REQUIRED, '任务');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $task = $input->getArgument('task');
        $task = TaskHelper::decode($task);

        $result = call_user_func($task);

        if (is_string($result)) {
            $output->writeln($result);
        } elseif (is_null($result)) {
            $output->writeln('');
        } else {
            $output->writeln(TaskHelper::encode($result));
        }

        return 0;
    }
}