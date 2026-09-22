<?php

/*
 * This file is part of the Symfony MakerBundle package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\MakerBundle\Command;

use Symfony\Bundle\MakerBundle\ApplicationAwareMakerInterface;
use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\DependencyBuilder;
use Symfony\Bundle\MakerBundle\Exception\CommandRestartedException;
use Symfony\Bundle\MakerBundle\Exception\RuntimeCommandException;
use Symfony\Bundle\MakerBundle\FileManager;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\InputConfiguration;
use Symfony\Bundle\MakerBundle\MakerInterface;
use Symfony\Bundle\MakerBundle\Validator;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;

/**
 * Used as the Command class for the makers.
 *
 * @internal
 */
final class MakerCommand extends Command
{
    private InputConfiguration $inputConfig;
    private ConsoleStyle $io;
    private bool $checkDependencies = true;

    public function __construct(
        private MakerInterface $maker,
        private FileManager $fileManager,
        private Generator $generator,
    ) {
        $this->inputConfig = new InputConfiguration();

        parent::__construct();
    }

    protected function configure(): void
    {
        $this->maker->configureCommand($this, $this->inputConfig);
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $this->io = new ConsoleStyle($input, $output);

        if (!$input->isInteractive()) {
            $this->io->warning(\sprintf('"%s" is not meant to be run in non-interactive mode.', $this->getName()));
        }

        $this->fileManager->setIO($this->io);

        if ($this->checkDependencies) {
            $dependencies = new DependencyBuilder();
            $this->maker->configureDependencies($dependencies, $input);

            if ($missingPackagesMessage = $dependencies->getMissingPackagesMessage($this->getName())) {
                $composer = $this->findComposer();

                if (!$input->isInteractive() || null === $composer || !$this->confirmPackagesInstallation($dependencies)) {
                    throw new RuntimeCommandException($missingPackagesMessage);
                }

                $this->installPackages($composer, $dependencies, $output);

                // the kernel of this process was built without the new packages, and running
                // "composer require" corrupts its container cache, so the maker cannot go on here
                throw new CommandRestartedException($this->restart());
            }
        }
    }

    protected function interact(InputInterface $input, OutputInterface $output): void
    {
        if (!$this->fileManager->isNamespaceConfiguredToAutoload($this->generator->getRootNamespace())) {
            $this->io->note([
                \sprintf('It looks like your app may be using a namespace other than "%s".', $this->generator->getRootNamespace()),
                'To configure this and make your life easier, see: https://symfony.com/doc/current/bundles/SymfonyMakerBundle/index.html#configuration',
            ]);
        }

        foreach ($this->getDefinition()->getArguments() as $argument) {
            if ($input->getArgument($argument->getName())) {
                continue;
            }

            if (\in_array($argument->getName(), $this->inputConfig->getNonInteractiveArguments(), true)) {
                continue;
            }

            $value = $this->io->ask($argument->getDescription(), $argument->getDefault(), Validator::notBlank(...));
            $input->setArgument($argument->getName(), $value);
        }

        $this->maker->interact($input, $this->io, $this);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->maker->generate($input, $this->io, $this->generator);

        // sanity check for custom makers
        if ($this->generator->hasPendingOperations()) {
            throw new \LogicException('Make sure to call the writeChanges() method on the generator.');
        }

        return 0;
    }

    public function run(InputInterface $input, OutputInterface $output): int
    {
        try {
            return parent::run($input, $output);
        } catch (CommandRestartedException $e) {
            return $e->exitCode;
        }
    }

    public function setApplication(?Application $application = null): void
    {
        parent::setApplication($application);

        if ($this->maker instanceof ApplicationAwareMakerInterface) {
            if (null === $application) {
                throw new \RuntimeException('Application cannot be null.');
            }

            $this->maker->setApplication($application);
        }
    }

    /**
     * @internal Used for testing commands
     */
    public function setCheckDependencies(bool $checkDeps): void
    {
        $this->checkDependencies = $checkDeps;
    }

    private function confirmPackagesInstallation(DependencyBuilder $dependencies): bool
    {
        $this->io->text(\sprintf('The %s command requires the following packages:', $this->getName()));
        $this->io->listing([...$dependencies->getMissingDependencies(), ...$dependencies->getMissingDevDependencies()]);

        return $this->io->confirm('Do you want to install these packages with Composer?');
    }

    /**
     * @param list<string> $composer The command that runs Composer, as found by findComposer()
     */
    private function installPackages(array $composer, DependencyBuilder $dependencies, OutputInterface $output): void
    {
        foreach ([[$dependencies->getMissingDependencies(), []], [$dependencies->getMissingDevDependencies(), ['--dev']]] as [$packages, $flags]) {
            if (!$packages) {
                continue;
            }

            $process = new Process([...$composer, 'require', ...$flags, ...$packages]);
            $process->setTimeout(null);

            if (Process::isTtySupported()) {
                $process->setTty(true);
            }

            $process->run(static function (string $type, string $buffer) use ($output): void {
                $output->write($buffer);
            });

            if (!$process->isSuccessful()) {
                throw new RuntimeCommandException(\sprintf('The "%s" command failed, the packages were not installed.', $process->getCommandLine()));
            }
        }
    }

    /**
     * The command that runs Composer, or null when there is none to find: a project may rely on a
     * global install, or ship its own phar, and offering to install packages is pointless without.
     *
     * @return list<string>|null
     */
    private function findComposer(): ?array
    {
        if ($composer = (new ExecutableFinder())->find('composer')) {
            return [$composer];
        }

        foreach (['composer.phar', 'composer'] as $file) {
            $path = $this->fileManager->getRootDirectory().'/'.$file;

            if (is_file($path)) {
                return [(new PhpExecutableFinder())->find() ?: \PHP_BINARY, $path];
            }
        }

        return null;
    }

    private function restart(): int
    {
        $argv = $_SERVER['argv'] ?? [];

        if (!$argv || !Process::isTtySupported()) {
            $this->io->success(\sprintf('The packages are installed, run the "%s" command again.', $this->getName()));

            return 0;
        }

        $process = new Process([(new PhpExecutableFinder())->find() ?: \PHP_BINARY, ...$argv]);
        $process->setTimeout(null);
        $process->setTty(true);

        return $process->run();
    }
}
