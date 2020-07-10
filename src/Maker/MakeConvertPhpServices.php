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

use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\DependencyBuilder;
use Symfony\Bundle\MakerBundle\Exception\RuntimeCommandException;
use Symfony\Bundle\MakerBundle\FileManager;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\InputConfiguration;
use Symfony\Bundle\MakerBundle\Util\PhpServicesCreator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\HttpKernel\Kernel;

/**
 * @author Antoine Michelet <jean.marcel.michelet@gmail.com>
 * @author Ryan Weaver <ryan@symfonycasts.com>
 *
 * @internal
 */
final class MakeConvertPhpServices extends AbstractMaker
{
    private $fileManager;

    public function __construct(FileManager $fileManager)
    {
        $this->fileManager = $fileManager;
    }

    public static function getCommandName(): string
    {
        return 'make:convert-php-services';
    }

    public function configureCommand(Command $command, InputConfiguration $inputConf)
    {
        $command->setDescription('Converts your services.yaml and services_{ENV}.yaml files into shiny services.php and services_{ENV}.php files');

        $command->addOption('confirm', 'c', InputOption::VALUE_NONE, 'Confirm that you want to perform the conversion.');
    }

    public function interact(InputInterface $input, ConsoleStyle $io, Command $command)
    {
        $command->addArgument('paths');
        $command->addArgument('newPaths');

        $paths = $this->findServicesFiles();

        $io->title('PHP Service Config? Yay!');
        $io->writeln(<<<EOF
This command will convert one or more YAML service files
(e.g. <comment>services.yaml</comment> or <comment>services_dev.yaml</comment>), into an equivalent
PHP version of those files and then delete the original YAML files.
Your <comment>src/Kernel.php</comment>', should automatically start loading the new files.
EOF
        );

        // TODO check for 5.1 Kernel recipe

        $io->title('Configuration');
        if (!empty($paths)) {
            $io->text(sprintf(
                'We found <info>%d</> service file%s to convert:',
                count($paths),
                count($paths) === 1 ? '' : 's'
            ));
            $io->writeln('');
            $io->listing(array_map(function($path) {
                return sprintf('<comment>%s</>', $path);
            }, $paths));

            $confirm = $io->confirm('Convert these files? Choose "n" to enter a specific file.');

            if ($confirm) {
                $input->setArgument('paths', $paths);

                return;
            }

            $io->writeln([
                'No problem! You can convert any files one-by-one.',
                'However, when you may need to manually add code to some files',
                'to make sure that each is loaded in the correct environment.',
            ]);
        }

        $path = $io->ask('Which file do you want to convert?', 'config/services.yaml', function ($value) {
            if (!$this->fileManager->fileExists($value)) {
                throw new \InvalidArgumentException(sprintf('File %s does not exist', $value));
            }

            return $value;
        });
        $input->setArgument('paths', ['all' => $path]);
    }

    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator)
    {
        $paths = $input->getArgument('paths');

        $newPathsMap = [];
        foreach ($paths as $path) {
            $newPath = str_replace(['.yml', '.yaml'], ['.php', '.php'], $path);

            if ($newPath === $path) {
                throw new RuntimeCommandException(sprintf('Could not determine a new filename for "%s". Does the file end in .yaml?', $path));
            }

            if ($this->fileManager->fileExists($newPath)) {
                throw new RuntimeCommandException(sprintf('Cannot convert file "%s": the target file "%s" already exists!', $path, $newPath));
            }

            $newPathsMap[$path] = $newPath;
        }

        // get an array of the files for other environments
        // *just* include their filename because we're assuming
        // that all files live directly in config/
        $environmentPaths = array_map(function($path) {
            return str_replace('config/', '', $path);
        }, $paths);
        unset($environmentPaths['all']);

        $phpServicesCreator = new PhpServicesCreator();
        $finalPhpContents = [];
        foreach ($paths as $environment => $path) {
            $yamlContents = $this->fileManager->getFileContents($path);
            try {
                $finalPhpContents[$path] = $phpServicesCreator->convert(
                    $yamlContents,
                    // for the main environment, import the other environment files
                    $environment === 'main' ? $environmentPaths : []
                );
            } catch (\Exception $e) {
                throw new RuntimeCommandException(sprintf('%s could not be converted. This may be a bug in your YAML or a missing feature in this command. Error: "%s"', $path, $e->getMessage()), 0, $e);
            }
        }

        foreach ($finalPhpContents as $path => $contents) {
            $generator->dumpFile($newPathsMap[$path], $contents);
            $generator->removeFile($path);
        }

        $generator->writeChanges();

        $this->writeSuccessMessage($io);

        $closing = [];
        $closing[] = 'Next:';
        $index = 0;
        // TODO - this is not right
        $kernelLoadsPhpConfig = str_contains($yamlContents, 'services.php');
        if (!$kernelLoadsPhpConfig) {
            $closing[] = sprintf('  %d) Make sure your <fg=yellow>src/Kernel.php</> file is loading the new PHP file.', ++$index);
            $closing[] = '      You may need to update your symfony/framework-bundle recipe.';
            $closing[] = '      Run <fg=yellow>composer recipes symfony/framework-bundle</> recipe to see if there is an update.';
        }

        $closing[] = sprintf('  %d) Review the new <fg=yellow>%s</> file and test your site!', ++$index, $newPath);

        $io->text($closing);
        $io->newLine();
        $io->text('Then open your browser, go to "/reset-password" and enjoy!');
        $io->newLine();
    }

    public function configureDependencies(DependencyBuilder $dependencies)
    {
        if (Kernel::VERSION_ID < 50100) {
            throw new RuntimeCommandException(sprintf('The "%s" command requires Symfony 5.1. What a great time to upgrade!', self::getCommandName()));
        }
    }

    /**
     * @return string[]
     */
    private function findServicesFiles(): array
    {
        $finder = $this->fileManager->createFinder('config')
            ->name(['services.yaml', 'services_*.yaml']);

        $serviceFilenames = [];
        foreach ($finder as $file) {
            if ($file->getFilename() === 'services.yaml') {
                $environment = 'all';
            } else {
                $environment = substr($file->getFilename(), 9, -5);
            }

            $serviceFilenames[$environment] = 'config/'.$file->getFilename();
        }

        return $serviceFilenames;
    }
}
