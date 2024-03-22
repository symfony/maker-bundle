<?php

namespace Symfony\Bundle\MakerBundle\Maker;

use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\DependencyBuilder;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\InputConfiguration;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;

/**
 * @author Jesse Rushlow <jr@rushlow.dev>
 */
class MakeScheduler extends AbstractMaker
{
    public static function getCommandName(): string
    {
        return 'make:scheduler';
    }

    public static function getCommandDescription(): string
    {
        return 'Create a scheduler component';
    }

    public function configureCommand(Command $command, InputConfiguration $inputConfig): void
    {
        $command
            ->setHelp(file_get_contents(__DIR__.'/../Resources/help/MakeScheduler.txt'))
        ;
    }

    public function interact(InputInterface $input, ConsoleStyle $io, Command $command): void
    {
        parent::interact($input, $io, $command);
    }

    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator): void
    {
        // TODO: Implement generate() method.
    }

    public function configureDependencies(DependencyBuilder $dependencies): void
    {
        // TODO: Implement configureDependencies() method.
    }
}
