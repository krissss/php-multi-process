<?php

namespace Kriss\MultiProcess\SymfonyConsole\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DynamicCallCommand extends Command
{
    public const COMMAND_NAME = 'multi-process:dynamic-call';

    protected static $defaultName = self::COMMAND_NAME;
    protected static $defaultDescription = '多进程动态调用入口';

    protected function configure()
    {
        $this->addArgument('class', InputArgument::REQUIRED, '类名');
        $this->addOption('method', null, InputOption::VALUE_OPTIONAL, '方法', 'handle');
        $this->addOption('arg', null, InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, '调用方法时的传参');
        $this->addOption('cachedArg', null, InputOption::VALUE_OPTIONAL, '通过 cache 传递过来的参数');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $class = $input->getArgument('class');
        $method = $input->getOption('method');
        $args = $input->getOption('arg');

        $result = call_user_func_array([$class, $method], $args);

        if (is_string($result)) {
            $output->writeln($result);
        } elseif (is_null($result)) {
            $output->writeln('');
        } else {
            $output->writeln(serialize($result));
        }

        return 0;
    }
}