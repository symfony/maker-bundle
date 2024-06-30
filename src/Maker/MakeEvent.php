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
use Symfony\Bundle\MakerBundle\Doctrine\DoctrineHelper;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\InputConfiguration;
use Symfony\Bundle\MakerBundle\Validator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * @author Ippei Sumida <ippey.s@gmail.com>
 */
class MakeEvent extends AbstractMaker
{
    public function __construct(
        private readonly DoctrineHelper $doctrineHelper,
    ) {
    }

    public static function getCommandName(): string
    {
        return 'make:event';
    }

    public static function getCommandDescription(): string
    {
        return 'Create a event class.';
    }

    public function configureCommand(Command $command, InputConfiguration $inputConfig): void
    {
        $command
            ->addArgument('name', InputArgument::OPTIONAL, 'The name of the event class (e.g. <fg=yellow>OrderPlacedEvent</>)')
            ->setHelp(file_get_contents(__DIR__.'/../Resources/help/MakeEvent.txt'))
        ;
    }

    public function configureDependencies(DependencyBuilder $dependencies): void
    {
        $dependencies
            ->addClassDependency(Event::class, 'event-dispatcher')
        ;
    }

    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator): void
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

        $fields = [];
        $useClasses = [];
        while (true) {
            $newField = $this->askForNextField($io);
            if (null === $newField) {
                break;
            }
            $fields[] = $newField;
            if (
                class_exists($this->doctrineHelper->getEntityNamespace().'\\'.$newField['type'])
                && !\in_array($this->doctrineHelper->getEntityNamespace().'\\'.$newField['type'], $useClasses, true)
            ) {
                $useClasses[] = $this->doctrineHelper->getEntityNamespace().'\\'.$newField['type'];
            }
        }

        asort($useClasses);
        $generator->generateClass(
            $eventClassNameDetails->getFullName(),
            'event/Event.tpl.php',
            [
                'event_class_name' => $eventClassNameDetails->getShortName(),
                'fields' => $fields,
                'useClasses' => $useClasses,
            ]
        );

        $generator->writeChanges();

        $this->writeSuccessMessage($io);

        $io->text([
            'Next: Open your event and add your logic.',
            'Find the documentation at <fg=yellow>https://symfony.com/doc/current/event_dispatcher.html</>',
        ]);
    }

    /**
     * @return array{'name': string, 'type': string, 'nullable': bool}|null
     */
    public function askForNextField(ConsoleStyle $io): ?array
    {
        $fieldName = $io->ask('Field name (press <fg=yellow>enter</> to stop adding fields)', null);
        if (null === $fieldName) {
            return null;
        }

        $question = new Question('Field type (e.g. <fg=yellow>string</>)', 'string');
        $autocompleteValues = ['string', 'int', 'float', 'bool', 'array', 'object', 'callable', 'iterable', 'void'];
        $autocompleteValues = array_merge($autocompleteValues, $this->doctrineHelper->getEntitiesForAutocomplete());
        $question->setAutocompleterValues($autocompleteValues);
        $question->setValidator([Validator::class, 'notBlank']);
        $fieldType = $io->askQuestion($question);

        $nullable = $io->confirm('Can this field be null (nullable)', false);

        return [
            'name' => $fieldName,
            'type' => $fieldType,
            'nullable' => $nullable,
        ];
    }
}
