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
use Symfony\Bundle\MakerBundle\Validator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * @author Ippei Sumida <ippey.s@gmail.com>
 */
class MakeEvent extends AbstractMaker
{
    public static function getCommandName(): string
    {
        return 'make:event';
    }

    public static function getCommandDescription(): string
    {
        return 'Create a event class.';
    }

    public function configureCommand(Command $command, InputConfiguration $inputConfig)
    {
        $command
            ->addArgument('name', InputArgument::OPTIONAL, 'The name of the event class (e.g. <fg=yellow>OrderPlacedEvent</>)')
            ->setHelp(file_get_contents(__DIR__.'/../Resources/help/MakeEvent.txt'))
        ;
    }

    public function configureDependencies(DependencyBuilder $dependencies)
    {
        $dependencies
            ->addClassDependency(Event::class, 'event-dispatcher')
        ;
    }

    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator)
    {
        $name = $input->getArgument('name');
        if (null === $name) {
            $name = $io->ask('Event class name (e.g. <fg=yellow>OrderPlacedEvent</>)', null, [Validator::class, 'notBlank']);
        }
        $eventClassNameDetails = $generator->createClassNameDetails(
            $name,
            'Event\\',
            'Event'
        );

        $generator->generateClass(
            $eventClassNameDetails->getFullName(),
            'event/Event.tpl.php',
            [
                'event_class_name' => $eventClassNameDetails->getShortName(),
            ]
        );

        $generator->writeChanges();

        $this->writeSuccessMessage($io);

        $io->text([
            'Next: Open your event and add your logic.',
            'Find the documentation at <fg=yellow>https://symfony.com/doc/current/event_dispatcher.html</>',
        ]);
    }
}
