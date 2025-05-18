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

use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Doctrine\Common\EventArgs;
use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\DependencyBuilder;
use Symfony\Bundle\MakerBundle\Doctrine\DoctrineEventRegistry;
use Symfony\Bundle\MakerBundle\Doctrine\DoctrineHelper;
use Symfony\Bundle\MakerBundle\Exception\RuntimeCommandException;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\InputConfiguration;
use Symfony\Bundle\MakerBundle\Str;
use Symfony\Bundle\MakerBundle\Util\UseStatementGenerator;
use Symfony\Bundle\MakerBundle\Validator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;

final class MakeDoctrineListener extends AbstractMaker
{
    public function __construct(
        private readonly DoctrineEventRegistry $doctrineEventRegistry,
        private readonly DoctrineHelper $doctrineHelper,
    ) {
    }

    public static function getCommandName(): string
    {
        return 'make:doctrine:listener';
    }

    public static function getCommandDescription(): string
    {
        return 'Creates a new doctrine event or entity listener class';
    }

    public function configureCommand(Command $command, InputConfiguration $inputConfig): void
    {
        $command
            ->addArgument('name', InputArgument::OPTIONAL, 'Choose a class name for your doctrine event or entity listener')
            ->addArgument('event', InputArgument::OPTIONAL, 'What event do you want to listen to?')
            ->addArgument('entity', InputArgument::OPTIONAL, 'What entity should the event be associate with?')
            ->setHelp($this->getHelpFileContents('MakeDoctrineListener.txt'));

        $inputConfig->setArgumentAsNonInteractive('event');
        $inputConfig->setArgumentAsNonInteractive('entity');
    }

    public function interact(InputInterface $input, ConsoleStyle $io, Command $command): void
    {
        $io->writeln('');

        $event = $input->getArgument('event');

        if (!$event) {
            $events = $this->doctrineEventRegistry->getAllEvents();

            $io->writeln(' <fg=green>Suggested Events:</>');
            $io->listing(array_map(function (string $event): string {
                if ($this->doctrineEventRegistry->isLifecycleEvent($event)) {
                    $event .= ' <fg=yellow>(Lifecycle)</>';
                }

                return $event;
            }, $events));

            $question = new Question($command->getDefinition()->getArgument('event')->getDescription());
            $question->setAutocompleterValues($events);
            $question->setValidator(Validator::notBlank(...));

            $input->setArgument('event', $event = $io->askQuestion($question));

            if ($this->doctrineEventRegistry->isLifecycleEvent($event) && !$input->getArgument('entity')) {
                $question = new ConfirmationQuestion(\sprintf('The "%s" event is a lifecycle event, would you like to associate it with a specific entity (entity listener)?', $event));

                if ($io->askQuestion($question)) {
                    $question = new Question($command->getDefinition()->getArgument('entity')->getDescription());
                    $question->setValidator(Validator::notBlank(...));
                    $question->setAutocompleterValues($this->doctrineHelper->getEntitiesForAutocomplete());

                    $input->setArgument('entity', $io->askQuestion($question));
                }
            }
        }

        if (!$this->doctrineEventRegistry->isLifecycleEvent($event) && $input->getArgument('entity')) {
            throw new RuntimeCommandException(\sprintf('The "%s" event is not a lifecycle event and cannot be associated with a specific entity.', $event));
        }
    }

    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator): void
    {
        $name = $input->getArgument('name');
        $event = $input->getArgument('event');

        $eventFullClassName = $this->doctrineEventRegistry->getEventClassName($event) ?? EventArgs::class;
        $eventClassName = Str::getShortClassName($eventFullClassName);

        $useStatements = new UseStatementGenerator([
            $eventFullClassName,
        ]);

        $eventConstFullClassName = $this->doctrineEventRegistry->getEventConstantClassName($event);
        $eventConstClassName = $eventConstFullClassName ? Str::getShortClassName($eventConstFullClassName) : null;

        if ($eventConstFullClassName) {
            $useStatements->addUseStatement($eventConstFullClassName);
        }

        $className = $generator->createClassNameDetails(
            $name,
            'EventListener\\',
            'Listener',
        )->getFullName();

        $templateVars = [
            'use_statements' => $useStatements,
            'method_name' => $event,
            'event' => $eventConstClassName ? \sprintf('%s::%s', $eventConstClassName, $event) : "'$event'",
            'event_arg' => \sprintf('%s $event', $eventClassName),
        ];

        if ($input->getArgument('entity')) {
            $this->generateEntityListenerClass($useStatements, $generator, $className, $templateVars, $input->getArgument('entity'));
        } else {
            $this->generateEventListenerClass($useStatements, $generator, $className, $templateVars);
        }

        $generator->writeChanges();

        $this->writeSuccessMessage($io);

        $io->text([
            'Next: Open your new listener class and start customizing it.',
            'Find the documentation at <fg=yellow>https://symfony.com/doc/current/doctrine/events.html</>',
        ]);
    }

    public function configureDependencies(DependencyBuilder $dependencies): void
    {
        $dependencies->addClassDependency(
            DoctrineBundle::class,
            'doctrine/doctrine-bundle',
        );
    }

    /**
     * @param array<string, mixed> $templateVars
     */
    private function generateEntityListenerClass(UseStatementGenerator $useStatements, Generator $generator, string $className, array $templateVars, string $entityClassName): void
    {
        $entityClassDetails = $generator->createClassNameDetails(
            $entityClassName,
            'Entity\\',
        );

        $useStatements->addUseStatement(AsEntityListener::class);
        $useStatements->addUseStatement($entityClassDetails->getFullName());

        $generator->generateClass(
            $className,
            'doctrine/EntityListener.tpl.php',
            $templateVars + [
                'entity' => $entityClassName,
                'entity_arg' => \sprintf('%s $entity', $entityClassName),
            ],
        );
    }

    /**
     * @param array<string, mixed> $templateVars
     */
    private function generateEventListenerClass(UseStatementGenerator $useStatements, Generator $generator, string $className, array $templateVars): void
    {
        $useStatements->addUseStatement(AsDoctrineListener::class);

        $generator->generateClass(
            $className,
            'doctrine/EventListener.tpl.php',
            $templateVars,
        );
    }
}
