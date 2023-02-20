<?php

namespace Kriss\MultiProcess;

use Closure;
use Symfony\Component\Process\Process;

class PendingProcess
{
    /** @var string|array */
    protected $command;
    protected ?string $cwd = null;
    protected array $env = [];
    protected ?int $timeout = 60;
    protected ?int $idleTimeout = null;
    /** @var mixed */
    protected $input;
    protected bool $quietly = false;
    protected array $options = [];
    protected ?Closure $startCallback = null;

    /**
     * @return static
     */
    final public static function create()
    {
        return new static();
    }

    /**
     * @param string|array $command
     * @return static
     */
    public static function createFromCommand($command)
    {
        return static::create()->setCommand($command);
    }

    /**
     * @return Process
     */
    public function toSymfonyProcess(): Process
    {
        $process = is_iterable($this->command)
            ? new Process($this->command, $this->cwd, $this->env)
            : Process::fromShellCommandline($this->command, $this->cwd, $this->env);

        $process->setTimeout($this->timeout);

        if ($this->idleTimeout) {
            $process->setIdleTimeout($this->idleTimeout);
        }

        if ($this->input) {
            $process->setInput($this->input);
        }

        if ($this->quietly) {
            $process->disableOutput();
        }

        if (!empty($this->options)) {
            $process->setOptions($this->options);
        }

        return $process;
    }

    /**
     * @return array|string
     */
    public function getCommand()
    {
        return $this->command;
    }

    /**
     * @param array|string $command
     * @return PendingProcess
     */
    public function setCommand($command)
    {
        $this->command = $command;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getCwd(): ?string
    {
        return $this->cwd;
    }

    /**
     * @param string|null $cwd
     * @return PendingProcess
     */
    public function setCwd(?string $cwd): PendingProcess
    {
        $this->cwd = $cwd;
        return $this;
    }

    /**
     * @return array
     */
    public function getEnv(): array
    {
        return $this->env;
    }

    /**
     * @param array $env
     * @return PendingProcess
     */
    public function setEnv(array $env): PendingProcess
    {
        $this->env = $env;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getTimeout(): ?int
    {
        return $this->timeout;
    }

    /**
     * @param int|null $timeout
     * @return PendingProcess
     */
    public function setTimeout(?int $timeout): PendingProcess
    {
        $this->timeout = $timeout;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getIdleTimeout(): ?int
    {
        return $this->idleTimeout;
    }

    /**
     * @param int|null $idleTimeout
     * @return PendingProcess
     */
    public function setIdleTimeout(?int $idleTimeout): PendingProcess
    {
        $this->idleTimeout = $idleTimeout;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getInput()
    {
        return $this->input;
    }

    /**
     * @param mixed $input
     * @return PendingProcess
     */
    public function setInput($input)
    {
        $this->input = $input;
        return $this;
    }

    /**
     * @return bool
     */
    public function isQuietly(): bool
    {
        return $this->quietly;
    }

    /**
     * @param bool $quietly
     * @return PendingProcess
     */
    public function setQuietly(bool $quietly = true): PendingProcess
    {
        $this->quietly = $quietly;
        return $this;
    }

    /**
     * @return array
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * @param array $options
     * @return PendingProcess
     */
    public function setOptions(array $options): PendingProcess
    {
        $this->options = $options;
        return $this;
    }

    /**
     * @return Closure|null
     */
    public function getStartCallback(): ?Closure
    {
        return $this->startCallback;
    }

    /**
     * @param Closure|null $startCallback
     * @return PendingProcess
     */
    public function setStartCallback(?Closure $startCallback): PendingProcess
    {
        $this->startCallback = $startCallback;
        return $this;
    }
}