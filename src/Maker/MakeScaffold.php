<?php

/*
 * This file is part of the Symfony MakerBundle package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\MakerBundle\Maker;

use Composer\InstalledVersions;
use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\DependencyBuilder;
use Symfony\Bundle\MakerBundle\FileManager;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\InputConfiguration;
use Symfony\Bundle\MakerBundle\JsPackageManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class MakeScaffold extends AbstractMaker
{
    private $files;
    private $jsPackageManager;
    private $availableScaffolds;
    private $composerBin;
    private $installedScaffolds = [];
    private $installedPackages = [];
    private $installedJsPackages = [];

    public function __construct(FileManager $files)
    {
        $this->files = $files;
        $this->jsPackageManager = new JsPackageManager($files);
    }

    public static function getCommandName(): string
    {
        return 'make:scaffold';
    }

    public static function getCommandDescription(): string
    {
        return 'Create scaffold'; // todo
    }

    public function configureCommand(Command $command, InputConfiguration $inputConfig): void
    {
        $command
            ->addArgument('name', InputArgument::OPTIONAL | InputArgument::IS_ARRAY, 'Scaffold name(s) to create')
        ;

        $inputConfig->setArgumentAsNonInteractive('name');
    }

    public function configureDependencies(DependencyBuilder $dependencies): void
    {
        $dependencies->addClassDependency(Process::class, 'process');
    }

    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator): void
    {
        $names = $input->getArgument('name');

        if (!$names) {
            throw new \InvalidArgumentException('You must select at least one scaffold.');
        }

        foreach ($names as $name) {
            $this->generateScaffold($name, $io);
        }

        if ($this->installedJsPackages) {
            if ($this->jsPackageManager->isAvailable()) {
                $io->comment('Installing JS packages...');
                $this->jsPackageManager->install();

                $io->comment('Running Webpack Encore...');
                $this->jsPackageManager->run('dev');
            } else {
                $io->warning('Unable to detect JS package manager, you need to run "yarn/npm install".');
            }
        }
    }

    public function interact(InputInterface $input, ConsoleStyle $io, Command $command): void
    {
        if ($input->getArgument('name')) {
            return;
        }

        $availableScaffolds = array_combine(
            array_keys($this->availableScaffolds()),
            array_map(fn (array $scaffold) => $scaffold['description'], $this->availableScaffolds())
        );

        $input->setArgument('name', [$io->choice('Available scaffolds', $availableScaffolds)]);
    }

    private function generateScaffold(string $name, ConsoleStyle $io): void
    {
        if ($this->isScaffoldInstalled($name)) {
            return;
        }

        if (!isset($this->availableScaffolds()[$name])) {
            throw new \InvalidArgumentException("Scaffold \"{$name}\" does not exist for your version of Symfony.");
        }

        $scaffold = $this->availableScaffolds()[$name];

        // install dependent scaffolds
        foreach ($scaffold['dependents'] ?? [] as $dependent) {
            $this->generateScaffold($dependent, $io);
        }

        $io->text("Installing <info>{$name}</info> Scaffold...");

        // install required packages
        foreach ($scaffold['packages'] ?? [] as $package => $env) {
            if (!$this->isPackageInstalled($package)) {
                $io->text("Installing Composer package: <comment>{$package}</comment>...");

                $command = [$this->composerBin(), 'require', '--no-scripts', 'dev' === $env ? '--dev' : null, $package];
                $process = new Process(array_filter($command), $this->files->getRootDirectory());

                $process->run();

                if (!$process->isSuccessful()) {
                    throw new \RuntimeException("Error installing \"{$package}\".");
                }

                $this->installedPackages[] = $package;
            }
        }

        // install required js packages
        foreach ($scaffold['js_packages'] ?? [] as $package => $version) {
            if (!\in_array($package, $this->installedJsPackages, true)) {
                $io->text("Installing JS package: <comment>{$package}@{$version}</comment>...");

                $this->jsPackageManager->add($package, $version);
                $this->installedJsPackages[] = $package;
            }
        }

        if (is_dir($scaffold['dir'])) {
            $io->text('Copying scaffold files...');

            (new Filesystem())->mirror($scaffold['dir'], $this->files->getRootDirectory());
        }

        if (isset($scaffold['configure'])) {
            $io->text('Executing configuration...');

            $scaffold['configure']($this->files);
        }

        $io->text("Successfully installed scaffold <info>{$name}</info>.");
        $io->newLine();

        $this->installedScaffolds[] = $name;
    }

    private function availableScaffolds(): array
    {
        if (\is_array($this->availableScaffolds)) {
            return $this->availableScaffolds;
        }

        $this->availableScaffolds = [];
        $finder = Finder::create()
            // todo, improve versioning system
            ->in(sprintf('%s/../Resources/scaffolds/%s.0', __DIR__, Kernel::MAJOR_VERSION))
            ->name('*.php')
            ->depth(0)
        ;

        foreach ($finder as $file) {
            $name = $file->getFilenameWithoutExtension();

            $this->availableScaffolds[$name] = array_merge(
                require $file,
                ['dir' => \dirname($file->getRealPath()).'/'.$name]
            );
        }

        return $this->availableScaffolds;
    }

    /**
     * Detect if package already installed or installed in this process
     * (when installing multiple scaffolds at once).
     */
    private function isPackageInstalled(string $package): bool
    {
        return InstalledVersions::isInstalled($package) || \in_array($package, $this->installedPackages, true);
    }

    /**
     * Detect if package is installed in the same process (when installing
     * multiple scaffolds at once).
     */
    private function isScaffoldInstalled(string $name): bool
    {
        return \in_array($name, $this->installedScaffolds, true);
    }

    private function composerBin(): string
    {
        if ($this->composerBin) {
            return $this->composerBin;
        }

        if (!$this->composerBin = (new ExecutableFinder())->find('composer')) {
            throw new \RuntimeException('Unable to detect composer binary.');
        }

        return $this->composerBin;
    }
}
