<?php

/*
 * This file is part of the Symfony package.
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
use Symfony\Bundle\MakerBundle\InputConfiguration;
use Symfony\Bundle\MakerBundle\MakerInterface;
use Symfony\Bundle\MakerBundle\Str;
use Symfony\Bundle\MakerBundle\Validator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Question\Question;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 * @author Ryan Weaver <weaverryan@gmail.com>
 */
final class MakeSubscriber implements MakerInterface
{
    private $eventRegistry;

    public function __construct(EventRegistry $eventRegistry)
    {
        $this->eventRegistry = $eventRegistry;
    }

    public static function getCommandName(): string
    {
        return 'make:subscriber';
    }

    public function configureCommand(Command $command, InputConfiguration $inputConf)
    {
        $command
            ->setDescription('Creates a new event subscriber class')
            ->addArgument('name', InputArgument::OPTIONAL, 'Choose a class name for your event subscriber (e.g. <fg=yellow>ExceptionSubscriber</>).')
            ->addArgument('event', InputArgument::OPTIONAL, 'What event do you want to subscribe to?')
            ->setHelp(file_get_contents(__DIR__.'/../Resources/help/MakeSubscriber.txt'))
        ;

        $inputConf->setArgumentAsNonInteractive('event');
    }

    public function interact(InputInterface $input, ConsoleStyle $io, Command $command)
    {
        if (!$input->getArgument('event')) {
            $events = $this->eventRegistry->getAllActiveEvents();

            $io->writeln(' <fg=green>Suggested Events:</>');
            $io->listing($events);
            $question = new Question(sprintf(' <fg=green>%s</>', $command->getDefinition()->getArgument('event')->getDescription()));
            $question->setAutocompleterValues($events);
            $question->setValidator([Validator::class, 'notBlank']);
            $event = $io->askQuestion($question);
            $input->setArgument('event', $event);
        }
    }

    public function getParameters(InputInterface $input): array
    {
        $subscriberClassName = Str::asClassName($input->getArgument('name'), 'Subscriber');
        Validator::validateClassName($subscriberClassName);
        $event = $input->getArgument('event');
        $eventClass = $this->eventRegistry->getEventClassName($event);
        $eventShortName = null;
        if ($eventClass) {
            $pieces = explode('\\', $eventClass);
            $eventShortName = end($pieces);
        }

        return [
            'subscriber_class_name' => $subscriberClassName,
            'event' => $event,
            'eventArg' => $eventShortName ? sprintf('%s $event', $eventShortName) : '$event',
            'methodName' => Str::asEventMethod($event),
            'eventClass' => $eventClass,
        ];
    }

    public function getFiles(array $params): array
    {
        return [
            __DIR__.'/../Resources/skeleton/event/Subscriber.tpl.php' => 'src/EventSubscriber/'.$params['subscriber_class_name'].'.php',
        ];
    }

    public function writeNextStepsMessage(array $params, ConsoleStyle $io)
    {
        $io->text([
            'Next: Open your new subscriber class and start customizing it.',
            'Find the documentation at <fg=yellow>https://symfony.com/doc/current/event_dispatcher.html#creating-an-event-subscriber</>',
        ]);
    }

    public function configureDependencies(DependencyBuilder $dependencies)
    {
    }
}
