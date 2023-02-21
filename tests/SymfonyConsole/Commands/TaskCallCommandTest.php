<?php

namespace Kriss\MultiProcessTests\SymfonyConsole\Commands;

use Kriss\MultiProcess\SymfonyConsole\Commands\TaskCallCommand;
use Kriss\MultiProcess\SymfonyConsole\Helper\TaskHelper;
use Kriss\MultiProcessTests\Fixtures\CallbackClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class TaskCallCommandTest extends TestCase
{
    public function testExecute()
    {
        $app = new Application();
        $app->add(new TaskCallCommand());
        $command = $app->find(TaskCallCommand::COMMAND_NAME);

        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'task' => TaskHelper::encode([CallbackClass::class, 'getHostname']),
        ]);
        $commandTester->assertCommandIsSuccessful();
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString(gethostname() . PHP_EOL, $output);

        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'task' => TaskHelper::encode(fn() => CallbackClass::getValue(null)),
        ]);
        $commandTester->assertCommandIsSuccessful();
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString(PHP_EOL, $output);

        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'task' => TaskHelper::encode(fn() => CallbackClass::getValue(123)),
        ]);
        $commandTester->assertCommandIsSuccessful();
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString(TaskHelper::encode(123), $output);
    }
}
