<?php

namespace Kriss\MultiProcessTests;

use Kriss\MultiProcess\SymfonyConsole\Commands\TaskCallCommand;
use Symfony\Component\Console\Output\OutputInterface;

class MyTaskCallCommand extends TaskCallCommand
{
    public function solveOutput($result, OutputInterface $output): void
    {
        $output->writeln('my task');

        parent::solveOutput($result, $output);
    }
}