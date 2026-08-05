<?php

/*
 * This file is part of the Symfony MakerBundle package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\MakerBundle\Test;

use Symfony\Component\Process\Process;

/**
 * @author Sadicov Vladimir <sadikoff@gmail.com>
 *
 * Frozen: kept for third-party bundles that test their own makers with it.
 * MakerBundle's own functional suite uses the internal tests/Harness engine
 * instead. Bug fixes are welcome, new features will not be added.
 *
 * @internal
 */
final class MakerTestProcess
{
    private Process $process;

    /**
     * @param string|list<string> $commandLine
     */
    private function __construct(string|array $commandLine, string $cwd, array $envVars, ?float $timeout)
    {
        $this->process = \is_string($commandLine)
            ? Process::fromShellCommandline($commandLine, $cwd, null, null, $timeout)
            : new Process($commandLine, $cwd, null, null, $timeout);

        $this->process->setEnv($envVars);
    }

    /**
     * @param string|list<string> $commandLine
     */
    public static function create(string|array $commandLine, string $cwd, array $envVars = [], ?float $timeout = null): self
    {
        return new self($commandLine, $cwd, $envVars, $timeout);
    }

    public function setInput($input): self
    {
        $this->process->setInput($input);

        return $this;
    }

    public function run($allowToFail = false, array $envVars = []): self
    {
        if (false !== ($timeout = getenv('MAKER_PROCESS_TIMEOUT'))) {
            if ('null' === $timeout) {
                $timeout = null;
            }

            // Setting a value of null allows for step debugging
            $this->process->setTimeout($timeout);
        }

        $this->process->run(null, $envVars);

        if (!$allowToFail && !$this->process->isSuccessful()) {
            throw new \Exception(\sprintf('Error running command: "%s". Output: "%s". Error: "%s"', $this->process->getCommandLine(), $this->process->getOutput(), $this->process->getErrorOutput()));
        }

        return $this;
    }

    public function isSuccessful(): bool
    {
        return $this->process->isSuccessful();
    }

    public function getOutput(): string
    {
        return $this->process->getOutput();
    }

    public function getErrorOutput(): string
    {
        return $this->process->getErrorOutput();
    }
}
