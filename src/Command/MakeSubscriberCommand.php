<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\MakerBundle\Command;

use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\DependencyBuilder;
use Symfony\Bundle\MakerBundle\EventRegistry;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\Str;
use Symfony\Bundle\MakerBundle\Validator;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 * @author Ryan Weaver <weaverryan@gmail.com>
 */
final class MakeSubscriberCommand extends AbstractCommand
{
    protected static $defaultName = 'make:subscriber';

    private $eventRegistry;

    public function __construct(Generator $generator, EventRegistry $eventRegistry)
    {
        parent::__construct($generator);

        $this->eventRegistry = $eventRegistry;
    }

    protected function configure()
    {
        $this
            ->setDescription('Creates a new event subscriber class')
            ->addArgument('name', InputArgument::OPTIONAL, 'Choose a class name for your event subscriber (e.g. <fg=yellow>ExceptionSubscriber</>).')
            ->addArgument('event', InputArgument::OPTIONAL, 'What event do you want to subscribe to?')
            ->setHelp(file_get_contents(__DIR__.'/../Resources/help/MakeSubscriber.txt'))
        ;

        $this->setArgumentAsNonInteractive('event');
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        parent::interact($input, $output);

        if (!$input->getArgument('event')) {
            $events = $this->eventRegistry->getAllActiveEvents();

            $this->io->writeln(' <fg=green>Suggested Events:</>');
            $this->io->listing($events);
            $question = new Question(sprintf(' <fg=green>%s</>', $this->getDefinition()->getArgument('event')->getDescription()));
            $question->setAutocompleterValues($events);
            $question->setValidator([Validator::class, 'notBlank']);
            $event = $this->io->askQuestion($question);
            $input->setArgument('event', $event);
        }
    }

    protected function getParameters(): array
    {
        $subscriberClassName = Str::asClassName($this->input->getArgument('name'), 'Subscriber');
        Validator::validateClassName($subscriberClassName);
        $event = $this->input->getArgument('event');
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
            'eventUseStatement' => $eventClass ? sprintf("use $eventClass;\n") : '',
        ];
    }

    protected function getFiles(array $params): array
    {
        return [
            __DIR__.'/../Resources/skeleton/event/Subscriber.php.txt' => 'src/EventSubscriber/'.$params['subscriber_class_name'].'.php',
        ];
    }

    protected function writeNextStepsMessage(array $params, ConsoleStyle $io)
    {
        $io->text([
            'Next: Open your new subscriber class and start customizing it.',
            'Find the documentation at <fg=yellow>https://symfony.com/doc/current/event_dispatcher.html#creating-an-event-subscriber</>'
        ]);
    }

    protected function configureDependencies(DependencyBuilder $dependencies)
    {
    }
}
