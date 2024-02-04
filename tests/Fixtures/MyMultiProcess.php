<?php

namespace Kriss\MultiProcessTests\Fixtures;

use Kriss\MultiProcess\MultiProcess;

class MyMultiProcess extends MultiProcess
{
    public function getMaxProcessCount(): ?int
    {
        return $this->maxProcessCount;
    }
}