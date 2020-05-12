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
        if (!$input->getOption('confirm')) {
            $input->setOption('confirm', $io->confirm('This command will completely remove your services.yaml file after completion. Ready to convert to services.php?', false));
        }
    }

    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator)
    {
        if (false === $input->getOption('confirm')) {
            return;
        }

        if (!$this->fileManager->fileExists('config/services.yaml')) {
            throw new RuntimeCommandException('The file "config/services.yaml" does not exist.');
        }

        $phpServicesContent = (new PhpServicesCreator())->convert(
            $this->fileManager->getFileContents('config/services.yaml')
        );

        $generator->dumpFile('config/services.php', $phpServicesContent);

        $generator->removeFile('config/services.yaml');

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
