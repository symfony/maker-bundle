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
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 * @author Ryan Weaver <weaverryan@gmail.com>
 * @author Steven Renaux <steven.renaux8000@gmail.com>
 */
final class MakeListener extends AbstractMaker
{
    private const ALL_TYPES = ['Listener', 'Subscriber'];
    private bool $isSubscriber = false;

    public function __construct(private readonly EventRegistry $eventRegistry)
    {
    }

    public static function getCommandName(): string
    {
        return 'make:listener';
    }

    /**
     * @deprecated remove this method when removing make:subscriber
     */
    public static function getCommandAlias(): string
    {
        return 'make:subscriber';
    }

    public static function getCommandDescription(): string
    {
        return 'Creates a new event subscriber class or a new event listener class';
    }

    public function configureCommand(Command $command, InputConfiguration $inputConfig): void
    {
        $command
            ->addArgument('name', InputArgument::OPTIONAL, 'Choose a class name for your event listener or subscriber (e.g. <fg=yellow>ExceptionListener</> or <fg=yellow>ExceptionSubscriber</>)')
            ->addArgument('event', InputArgument::OPTIONAL, 'What event do you want to listen to?')
            ->setHelp(file_get_contents(__DIR__.'/../Resources/help/MakeListener.txt'))
        ;

        $inputConfig->setArgumentAsNonInteractive('event');
    }

    public function interact(InputInterface $input, ConsoleStyle $io, Command $command): void
    {
        /* @deprecated remove the following block when removing make:subscriber */
        $this->handleDeprecatedMakerCommands($input, $io);

        $io->writeln('');

        $name = $input->getArgument('name');

        if (!str_ends_with($name, 'Subscriber') && !str_ends_with($name, 'Listener')) {
            $question = new ChoiceQuestion('Do you want to generate an event listener or subscriber?', self::ALL_TYPES, 0);
            $classToGenerate = $io->askQuestion($question);

            $input->setArgument('name', $name.$classToGenerate);
        }

        if (str_ends_with($input->getArgument('name'), 'Subscriber')) {
            $this->isSubscriber = true;
        }

        if (!$input->getArgument('event')) {
            $events = $this->eventRegistry->getAllActiveEvents();

            $io->writeln(' <fg=green>Suggested Events:</>');
            $io->listing($this->eventRegistry->listActiveEvents($events));
            $question = new Question(sprintf(' <fg=green>%s</>', $command->getDefinition()->getArgument('event')->getDescription()));
            $question->setAutocompleterValues($events);
            $question->setValidator([Validator::class, 'notBlank']);
            $event = $io->askQuestion($question);
            $input->setArgument('event', $event);
        }
    }

    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator): void
    {
        if ($this->isSubscriber) {
            $useStatements = new UseStatementGenerator([
                EventSubscriberInterface::class,
            ]);
        } else {
            $useStatements = new UseStatementGenerator([
                AsEventListener::class,
            ]);
        }

        $event = $input->getArgument('event');
        $eventFullClassName = $this->eventRegistry->getEventClassName($event);
        $eventClassName = $eventFullClassName ? Str::getShortClassName($eventFullClassName) : null;

        if (null !== ($eventConstant = $this->getEventConstant($event))) {
            $useStatements->addUseStatement(KernelEvents::class);
            $eventName = $eventConstant;
        } else {
            $eventName = class_exists($event) ? sprintf('%s::class', $eventClassName) : sprintf('\'%s\'', $event);
        }

        if (null !== $eventFullClassName) {
            $useStatements->addUseStatement($eventFullClassName);
        }

        if ($this->isSubscriber) {
            $this->generateSubscriberClass($input, $io, $generator, $useStatements, $event, $eventName, $eventClassName);
        } else {
            $this->generateListenerClass($input, $io, $generator, $useStatements, $event, $eventName, $eventClassName);
        }
    }

    public function configureDependencies(DependencyBuilder $dependencies)
    {
    }

    private function getEventConstant(string $event): ?string
    {
        $constants = (new \ReflectionClass(KernelEvents::class))->getConstants();

        if (false !== ($name = array_search($event, $constants, true))) {
            return sprintf('KernelEvents::%s', $name);
        }

        return null;
    }

    private function generateSubscriberClass(InputInterface $input, ConsoleStyle $io, Generator $generator, UseStatementGenerator $useStatements, string $event, string $eventName, ?string $eventClassName): void
    {
        $subscriberClassNameDetails = $generator->createClassNameDetails(
            $input->getArgument('name'),
            'EventSubscriber\\',
            'Subscriber'
        );

        $generator->generateClass(
            $subscriberClassNameDetails->getFullName(),
            'event/Subscriber.tpl.php',
            [
                'use_statements' => $useStatements,
                'event' => $eventName,
                'event_arg' => $eventClassName ? sprintf('%s $event', $eventClassName) : '$event',
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

    private function generateListenerClass(InputInterface $input, ConsoleStyle $io, Generator $generator, UseStatementGenerator $useStatements, string $event, string $eventName, ?string $eventClassName): void
    {
        $listenerClassNameDetails = $generator->createClassNameDetails(
            $input->getArgument('name'),
            'EventListener\\',
            'Listener'
        );

        $generator->generateClass(
            $listenerClassNameDetails->getFullName(),
            'event/Listener.tpl.php',
            [
                'use_statements' => $useStatements,
                'event' => $eventName,
                'event_arg' => $eventClassName ? sprintf('%s $event', $eventClassName) : '$event',
                'method_name' => class_exists($event) ? Str::asEventMethod($eventClassName) : Str::asEventMethod($event),
            ]
        );

        $generator->writeChanges();

        $this->writeSuccessMessage($io);

        $io->text([
            'Next: Open your new listener class and start customizing it.',
            'Find the documentation at <fg=yellow>https://symfony.com/doc/current/event_dispatcher.html#creating-an-event-listener</>',
        ]);
    }

    /**
     * @deprecated
     */
    private function handleDeprecatedMakerCommands(InputInterface $input, ConsoleStyle $io): void
    {
        $currentCommand = $input->getFirstArgument();
        $name = $input->getArgument('name');

        if ('make:subscriber' === $currentCommand) {
            if (!str_ends_with($name, 'Subscriber')) {
                $input->setArgument('name', $name.'Subscriber');
            }

            $io->warning('The "make:subscriber" command is deprecated, use "make:listener" instead.');
        }
    }
}
