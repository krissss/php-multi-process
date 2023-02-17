<?php

namespace Kriss\MultiProcess;

use Symfony\Component\Process\Process;

class MultiProcessResults
{
    /**
     * @var array<string, Process>
     */
    protected array $results = [];

    public function __construct(array $results)
    {
        $this->results = $results;
    }

    public function getProcesses(): array
    {
        return $this->results;
    }

    public function getProcess(string $name): ?Process
    {
        return $this->results[$name] ?? null;
    }

    public function getOutputs(): array
    {
        $data = [];
        foreach ($this->getProcesses() as $name => $process) {
            $data[$name] = trim($process->getOutput());
        }
        return $data;
    }

    public function getOutput(string $name): string
    {
        if ($process = $this->getProcess($name)) {
            return trim($process->getOutput());
        }
        return '';
    }
}