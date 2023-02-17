<?php

namespace Kriss\MultiProcess;

use Closure;
use Symfony\Component\Process\Process;

class PendingProcess extends Process
{
    protected ?Closure $startCallback = null;

    /**
     * @return null|Closure
     */
    public function getStartCallback(): ?Closure
    {
        return $this->startCallback;
    }

    /**
     * @param null|Closure $startCallback
     * @return $this
     */
    public function setStartCallback(?Closure $startCallback): self
    {
        $this->startCallback = $startCallback;
        return $this;
    }
}