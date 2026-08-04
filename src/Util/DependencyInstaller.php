<?php

/*
 * This file is part of the Symfony MakerBundle package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\MakerBundle\Util;

use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\Exception\RuntimeCommandException;
use Symfony\Component\Process\Process;

/**
 * Installs composer packages a maker needs at runtime. Single abstraction for
 * every maker that auto-installs dependencies, so the behavior (and its
 * failure mode) is defined — and testable — in exactly one place.
 *
 * @author Jesse Rushlow <jr@rushlow.dev>
 *
 * @internal
 */
class DependencyInstaller
{
    /**
     * @param ?\Closure(string): Process $processFactory only meant for tests,
     *                                                   to run a harmless command instead of composer
     */
    public function __construct(
        private ?\Closure $processFactory = null,
    ) {
    }

    public function installPackage(ConsoleStyle $io, string $composerPackage): void
    {
        $command = \sprintf('composer require %s', $composerPackage);

        $io->writeln(\sprintf('Running: %s', $command));

        $factory = $this->processFactory ?? static fn (string $commandLine): Process => Process::fromShellCommandline($commandLine);
        $process = $factory($command);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new RuntimeCommandException(\sprintf("Could not install \"%s\":\n%s", $composerPackage, trim($process->getOutput()."\n".$process->getErrorOutput())));
        }

        $io->writeln(\sprintf('%s successfully installed!', $composerPackage));
        $io->newLine();
    }
}
