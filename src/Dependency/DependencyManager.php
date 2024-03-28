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

    public function __construct(
        private ConsoleStyle $io,
        private bool $interactiveMode = true,
    ) {
    }

    public function addDependency(RequiredClassDependency|OptionalClassDependency|array $dependency): self
    {
        $dependencies = [];

        if (!\is_array($dependency)) {
            $dependencies[] = $dependency;
        }

        foreach ($dependencies as $dependency) {
            if ($dependency instanceof RequiredClassDependency) {
                $this->requiredClassDependencies[] = $dependency;

                continue;
            }

            $this->optionalClassDependencies[] = $dependency;
        }

        return $this;
    }

    public function installRequiredDependencies(): self
    {
        foreach ($this->requiredClassDependencies as $dependency) {
            if (class_exists($dependency->className) || !$this->askToInstallDependency($dependency)) {
                continue;
            }

            $dependency->preInstallMessage ?: $this->io->caution($dependency->preInstallMessage);

            $this->runComposer($dependency);
        }

        return $this;
    }

    public function installOptionalDependencies(): self
    {
        foreach ($this->optionalClassDependencies as $dependency) {
            if (class_exists($dependency->className) || !$this->askToInstallDependency($dependency)) {
                continue;
            }

            $dependency->preInstallMessage ?: $this->io->caution($dependency->preInstallMessage);

            $this->runComposer($dependency);
        }

        return $this;
    }

    public function installInteractively(): bool
    {
        return $this->interactiveMode;
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
