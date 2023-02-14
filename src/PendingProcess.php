<?php

namespace Kriss\MultiProcess;

use Symfony\Component\Process\Process;

class PendingProcess extends Process
{
    protected $startCallback = null;

    /**
     * @return null
     */
    public function getStartCallback()
    {
        return $this->startCallback;
    }

    /**
     * @param null $startCallback
     * @return PendingProcess
     */
    public function setStartCallback($startCallback)
    {
        $this->startCallback = $startCallback;
        return $this;
    }
}