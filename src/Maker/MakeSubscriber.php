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
use Symfony\Bundle\MakerBundle\EventRegistry;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\InputConfiguration;
use Symfony\Bundle\MakerBundle\Str;
use Symfony\Bundle\MakerBundle\Util\UseStatementGenerator;
use Symfony\Bundle\MakerBundle\Validator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;

trigger_deprecation('symfony/maker-bundle', '1.51', 'The "%s" class is deprecated, use "%s" instead.', MakeSubscriber::class, MakeListener::class);

/**
 * @deprecated since MakerBundle 1.51, use Symfony\Bundle\MakerBundle\Maker\MakeListener instead.
 *
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 * @author Ryan Weaver <weaverryan@gmail.com>
 */
final class MakeSubscriber extends AbstractMaker
{
    public function __construct(private EventRegistry $eventRegistry)
    {
    }

    public static function getCommandName(): string
    {
        return 'make:subscriber';
    }

    public static function getCommandDescription(): string
    {
        return 'Create a new event subscriber class';
    }

    public function configureCommand(Command $command, InputConfiguration $inputConfig): void
    {
        $command
            ->addArgument('name', InputArgument::OPTIONAL, 'Choose a class name for your event subscriber (e.g. <fg=yellow>ExceptionSubscriber</>)')
            ->addArgument('event', InputArgument::OPTIONAL, 'What event do you want to subscribe to?')
            ->setHelp(file_get_contents(__DIR__.'/../Resources/help/MakeSubscriber.txt'))
        ;

        $inputConfig->setArgumentAsNonInteractive('event');
    }

    public function interact(InputInterface $input, ConsoleStyle $io, Command $command): void
    {
        if (!$input->getArgument('event')) {
            $events = $this->eventRegistry->getAllActiveEvents();

            $io->writeln(' <fg=green>Suggested Events:</>');
            $io->listing($this->eventRegistry->listActiveEvents($events));
            $question = new Question(\sprintf(' <fg=green>%s</>', $command->getDefinition()->getArgument('event')->getDescription()));
            $question->setAutocompleterValues($events);
            $question->setValidator(Validator::notBlank(...));
            $event = $io->askQuestion($question);
            $input->setArgument('event', $event);
        }
    }

    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator): void
    {
        $subscriberClassNameDetails = $generator->createClassNameDetails(
            $input->getArgument('name'),
            'EventSubscriber\\',
            'Subscriber'
        );

        $event = $input->getArgument('event');
        $eventFullClassName = $this->eventRegistry->getEventClassName($event);
        $eventClassName = $eventFullClassName ? Str::getShortClassName($eventFullClassName) : null;

        $useStatements = new UseStatementGenerator([
            EventSubscriberInterface::class,
        ]);

        // Determine if we use a KernelEvents::CONSTANT or custom even name
        if (null !== ($eventConstant = $this->getEventConstant($event))) {
            $useStatements->addUseStatement(KernelEvents::class);
            $eventName = $eventConstant;
        } else {
            $eventName = class_exists($event) ? \sprintf('%s::class', $eventClassName) : \sprintf('\'%s\'', $event);
        }

        if (null !== $eventFullClassName) {
            $useStatements->addUseStatement($eventFullClassName);
        }

        $generator->generateClass(
            $subscriberClassNameDetails->getFullName(),
            'event/Subscriber.tpl.php',
            [
                'use_statements' => $useStatements,
                'event' => $eventName,
                'event_arg' => $eventClassName ? \sprintf('%s $event', $eventClassName) : '$event',
                'method_name' => class_exists($event) ? Str::asEventMethod($eventClassName) : Str::asEventMethod($event),
            ]
        );

        $generator->writeChanges();

        $this->writeSuccessMessage($io);

        $io->text([
            'Next: Open your new subscriber class and start customizing it.',
            'Find the documentation at <fg=yellow>https://symfony.com/doc/current/event_dispatcher.html#creating-an-event-subscriber</>',
        ]);
    }

    public function configureDependencies(DependencyBuilder $dependencies): void
    {
    }

    private function getEventConstant(string $event): ?string
    {
        $constants = (new \ReflectionClass(KernelEvents::class))->getConstants();

        if (false !== ($name = array_search($event, $constants, true))) {
            return \sprintf('KernelEvents::%s', $name);
        }

        return null;
    }
}
