<?php

use Kriss\MultiProcess\MultiProcess;

it('test DynamicCallCommand', function () {
    $results = MultiProcess::create()
        ->addDynamicCall([\Kriss\MultiProcessTests\SymfonyConsoleTestClass::class, 'handle'], [1, 2], 'my-name')
        ->wait();

    $this->assertEquals(unserialize(trim($results->getOutput('my-name'))), ['param1' => 1, 'param2' => 2]);
});
