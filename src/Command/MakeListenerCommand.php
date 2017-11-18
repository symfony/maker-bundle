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
 * Based on MakeSubscriberCommand.
 *
 * @author Piotr Grabski-Gradzinski <piotr.gradzinski@gmail.com>
 */
final class MakeListenerCommand extends AbstractCommand
{
    protected static $defaultName = 'make:listener';

    private $eventRegistry;

    public function __construct(Generator $generator, EventRegistry $eventRegistry)
    {
        parent::__construct($generator);

        $this->eventRegistry = $eventRegistry;
    }

    protected function configure()
    {
        $this
            ->setDescription('Creates a new event listener class')
            ->addArgument('name', InputArgument::OPTIONAL, 'Choose a class name for your event listener (e.g. <fg=yellow>ExceptionListener</>).')
            ->addArgument('event', InputArgument::OPTIONAL, 'What event do you want to listen to?')
            ->setHelp(file_get_contents(__DIR__.'/../Resources/help/MakeListener.txt'))
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
            $event = $this->io->askQuestion($question);
            $input->setArgument('event', $event);
        }
    }

    protected function getParameters(): array
    {
        $listenerClassName = Str::asClassName($this->input->getArgument('name'), 'Listener');
        Validator::validateClassName($listenerClassName);
        $event = $this->input->getArgument('event');
        $eventClass = $this->eventRegistry->getEventClassName($event);
        $eventShortName = null;
        if ($eventClass) {
            $pieces = explode('\\', $eventClass);
            $eventShortName = end($pieces);
        }

        return [
            'listener_class_name' => $listenerClassName,
            'event' => $event,
            'eventArg' => $eventShortName ? sprintf('%s $event', $eventShortName) : '$event',
            'methodName' => Str::asEventMethod($event),
            'eventUseStatement' => $eventClass ? sprintf("use $eventClass;\n") : '',
        ];
    }

    protected function getFiles(array $params): array
    {
        return [
            __DIR__.'/../Resources/skeleton/event/Listener.php.txt' => 'src/EventListener/'.$params['listener_class_name'].'.php'
        ];
    }

    protected function writeNextStepsMessage(array $params, ConsoleStyle $io)
    {
        $io->section('Next');

        $io->text([
            'Register you new listener as a service. Append the following code to your <fg=yellow>config/services.yaml</>',
        ]);

        $io->newLine();

        $io->writeln([
            sprintf('    App\EventListener\%s:', $params['listener_class_name']),
            sprintf('        tags:'),
            sprintf('            - { name: kernel.event_listener, event: %s }', $params['event']),
        ]);

        $io->newLine();

        $io->text([
            'Open your new listener class and start customizing it.',
            'Find the documentation at <fg=yellow>https://symfony.com/doc/current/event_dispatcher.html#creating-an-event-listener</>'
        ]);

    }

    protected function configureDependencies(DependencyBuilder $dependencies)
    {
    }
}
