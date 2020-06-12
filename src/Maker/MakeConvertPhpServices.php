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
        $command->setDescription('Converts your services.yaml file into a shiny services.php file');

        $command->addOption('confirm', 'c', InputOption::VALUE_NONE, 'Confirm that you want to perform the conversion.');
    }

    public function interact(InputInterface $input, ConsoleStyle $io, Command $command)
    {
        $command->addArgument('path');
        $command->addArgument('newPath');

        $path = $io->ask('What file do you want to convert?', 'config/services.yaml', function($value) {
            if (!$this->fileManager->fileExists($value)) {
                throw new \InvalidArgumentException(sprintf('File %s does not exist', $value));
            }

            return $value;
        });
        $input->setArgument('path', $path);

        $newPath = str_replace(['.yml', '.yaml'], ['.php', '.php'], $path);
        if ($newPath === $path) {
            $newPath = $io->ask('What filename should be used for the new file?', 'config/services.php', function($value) {
                if ($this->fileManager->fileExists($value)) {
                    throw new \InvalidArgumentException(sprintf('File %s already exists', $value));
                }

                return $value;
            });
        }
        $input->setArgument('newPath', $newPath);

        if (!$input->getOption('confirm')) {
            $input->setOption('confirm', $io->confirm(sprintf(
                'This command will completely remove your <fg=yellow>%s</> file after completion. Ready to convert to services.php?',
                $path,
            ), false));
        }
    }

    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator)
    {
        if (false === $input->getOption('confirm')) {
            return;
        }

        $path = $input->getArgument('path');
        $phpServicesContent = (new PhpServicesCreator())->convert(
            $this->fileManager->getFileContents($path)
        );

        $newPath = $input->getArgument('newPath');
        $generator->dumpFile($newPath, $phpServicesContent);

        $generator->removeFile($path);

        $generator->writeChanges();

        $this->writeSuccessMessage($io);
    }

    public function configureDependencies(DependencyBuilder $dependencies)
    {
        // if (Kernel::VERSION_ID < 50100) {
        //     throw new RuntimeCommandException(sprintf('The "%s" command requires Symfony 5.1. What a great time to upgrade!', self::getCommandName()));
        // }
    }
}
