<?php

/*
 * This file is part of the Symfony MakerBundle package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\MakerBundle\Dependency;

use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\Dependency\Model\OptionalClassDependency;
use Symfony\Bundle\MakerBundle\Dependency\Model\RequiredClassDependency;
use Symfony\Bundle\MakerBundle\Exception\RuntimeCommandException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Process\Process;

/**
 * @author Jesse Rushlow<jr@rushlow.dev>
 *
 * @internal
 */
final class DependencyManager
{
    /** @var RequiredClassDependency[] */
    private array $requiredClassDependencies = [];

    /** @var OptionalClassDependency[] */
    private array $optionalClassDependencies = [];

    private ConsoleStyle $io;

    public function addRequiredDependency(RequiredClassDependency $dependency): self
    {
        $this->requiredClassDependencies[] = $dependency;

        return $this;
    }

    public function addOptionalDependency(OptionalClassDependency $dependency): self
    {
        $this->optionalClassDependencies[] = $dependency;

        return $this;
    }

    public function installRequiredDependencies(ConsoleStyle $io, ?string $preInstallMessage): self
    {
        $this->io = $io;

        $preInstallMessage ?: $this->io->caution($preInstallMessage);

        foreach ($this->requiredClassDependencies as $dependency) {
            if (class_exists($dependency->className) || !$this->askToInstallDependency($dependency)) {
                continue;
            }

            $this->runComposer($dependency);
        }

        return $this;
    }

    public function installOptionalDependencies(ConsoleStyle $io, ?string $preInstallMessage): self
    {
        $this->io = $io;

        $preInstallMessage ?: $this->io->caution($preInstallMessage);

        foreach ($this->optionalClassDependencies as $dependency) {
            if (class_exists($dependency->className) || !$this->askToInstallDependency($dependency)) {
                continue;
            }

            $this->runComposer($dependency);
        }

        return $this;
    }

    private function askToInstallDependency(RequiredClassDependency|OptionalClassDependency $dependency): bool
    {
        return $this->io->confirm(
            question: sprintf('Do you want us to run <fg=yellow>composer require %s</> for you?', $dependency->composerPackage),
            default: true // @TODO - Should we default to yes or no on this...
        );
    }

    private function runComposer(RequiredClassDependency|OptionalClassDependency $dependency): void
    {
        $process = Process::fromShellCommandline(
            sprintf('composer require%s %s', $dependency->installAsRequireDev ?: ' --dev', $dependency->composerPackage)
        );

        if (Command::SUCCESS === $process->run()) {
            return;
        }

        $this->io->block($process->getErrorOutput());

        throw new RuntimeCommandException(sprintf('Oops! There was a problem installing "%s". You\'ll need to install the package manually.', $dependency->className));
    }
}
