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
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\InputConfiguration;
use Symfony\Bundle\MakerBundle\Str;
use Symfony\Bundle\MakerBundle\Util\PhpCompatUtil;
use Symfony\Bundle\MakerBundle\Util\UseStatementGenerator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\LazyCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 * @author Ryan Weaver <weaverryan@gmail.com>
 */
final class MakeCommand extends AbstractMaker
{
    public function __construct(private ?PhpCompatUtil $phpCompatUtil = null)
    {
        if (null !== $phpCompatUtil) {
            @trigger_deprecation(
                'symfony/maker-bundle',
                '1.55.0',
                \sprintf('Initializing MakeCommand while providing an instance of "%s" is deprecated. The $phpCompatUtil param will be removed in a future version.', PhpCompatUtil::class),
            );
        }
    }

    public static function getCommandName(): string
    {
        return 'make:command';
    }

    public static function getCommandDescription(): string
    {
        return 'Create a new console command class';
    }

    public function configureCommand(Command $command, InputConfiguration $inputConfig): void
    {
        $command
            ->addArgument('name', InputArgument::OPTIONAL, \sprintf('Choose a command name (e.g. <fg=yellow>app:%s</>)', Str::asCommand(Str::getRandomTerm())))
            ->setHelp(file_get_contents(__DIR__.'/../Resources/help/MakeCommand.txt'))
        ;
    }

    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator): void
    {
        $commandName = trim($input->getArgument('name'));
        $commandNameHasAppPrefix = str_starts_with($commandName, 'app:');

        $commandClassNameDetails = $generator->createClassNameDetails(
            $commandNameHasAppPrefix ? substr($commandName, 4) : $commandName,
            'Command\\',
            'Command',
            \sprintf('The "%s" command name is not valid because it would be implemented by "%s" class, which is not valid as a PHP class name (it must start with a letter or underscore, followed by any number of letters, numbers, or underscores).', $commandName, Str::asClassName($commandName, 'Command'))
        );

        $useStatements = new UseStatementGenerator([
            Command::class,
            InputArgument::class,
            InputInterface::class,
            InputOption::class,
            OutputInterface::class,
            SymfonyStyle::class,
            AsCommand::class,
        ]);

        $generator->generateClass(
            $commandClassNameDetails->getFullName(),
            'command/Command.tpl.php',
            [
                'use_statements' => $useStatements,
                'command_name' => $commandName,
                'set_description' => !class_exists(LazyCommand::class),
            ]
        );

        $generator->writeChanges();

        $this->writeSuccessMessage($io);
        $io->text([
            'Next: open your new command class and customize it!',
            'Find the documentation at <fg=yellow>https://symfony.com/doc/current/console.html</>',
        ]);
    }

    public function configureDependencies(DependencyBuilder $dependencies): void
    {
        $dependencies->addClassDependency(
            Command::class,
            'console'
        );
    }
}
