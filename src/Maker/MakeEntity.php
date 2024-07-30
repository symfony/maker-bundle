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

use ApiPlatform\Metadata\ApiResource;
use Doctrine\DBAL\Types\Type;
use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\DependencyBuilder;
use Symfony\Bundle\MakerBundle\Doctrine\DoctrineHelper;
use Symfony\Bundle\MakerBundle\Doctrine\EntityClassGenerator;
use Symfony\Bundle\MakerBundle\Doctrine\EntityRegenerator;
use Symfony\Bundle\MakerBundle\Doctrine\EntityRelation;
use Symfony\Bundle\MakerBundle\Doctrine\ORMDependencyBuilder;
use Symfony\Bundle\MakerBundle\Exception\RuntimeCommandException;
use Symfony\Bundle\MakerBundle\FileManager;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\InputAwareMakerInterface;
use Symfony\Bundle\MakerBundle\InputConfiguration;
use Symfony\Bundle\MakerBundle\Maker\Common\UidTrait;
use Symfony\Bundle\MakerBundle\Str;
use Symfony\Bundle\MakerBundle\Util\ClassDetails;
use Symfony\Bundle\MakerBundle\Util\ClassSource\Model\ClassProperty;
use Symfony\Bundle\MakerBundle\Util\ClassSourceManipulator;
use Symfony\Bundle\MakerBundle\Util\CliOutputHelper;
use Symfony\Bundle\MakerBundle\Validator;
use Symfony\Bundle\MercureBundle\DependencyInjection\MercureExtension;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\UX\Turbo\Attribute\Broadcast;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 * @author Ryan Weaver <weaverryan@gmail.com>
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class MakeEntity extends AbstractMaker implements InputAwareMakerInterface
{
    use UidTrait;

    private Generator $generator;
    private EntityClassGenerator $entityClassGenerator;

    public function __construct(
        private FileManager $fileManager,
        private DoctrineHelper $doctrineHelper,
        ?string $projectDirectory = null,
        ?Generator $generator = null,
        ?EntityClassGenerator $entityClassGenerator = null,
    ) {
        if (null !== $projectDirectory) {
            @trigger_error('The $projectDirectory constructor argument is no longer used since 1.41.0', \E_USER_DEPRECATED);
        }

        if (null === $generator) {
            @trigger_error(\sprintf('Passing a "%s" instance as 4th argument is mandatory since version 1.5.', Generator::class), \E_USER_DEPRECATED);
            $this->generator = new Generator($fileManager, 'App\\');
        } else {
            $this->generator = $generator;
        }

        if (null === $entityClassGenerator) {
            @trigger_error(\sprintf('Passing a "%s" instance as 5th argument is mandatory since version 1.15.1', EntityClassGenerator::class), \E_USER_DEPRECATED);
            $this->entityClassGenerator = new EntityClassGenerator($generator, $this->doctrineHelper);
        } else {
            $this->entityClassGenerator = $entityClassGenerator;
        }
    }

    public static function getCommandName(): string
    {
        return 'make:entity';
    }

    public static function getCommandDescription(): string
    {
        return 'Create or update a Doctrine entity class, and optionally an API Platform resource';
    }

    public function configureCommand(Command $command, InputConfiguration $inputConfig): void
    {
        $command
            ->addArgument('name', InputArgument::OPTIONAL, \sprintf('Class name of the entity to create or update (e.g. <fg=yellow>%s</>)', Str::asClassName(Str::getRandomTerm())))
            ->addOption('api-resource', 'a', InputOption::VALUE_NONE, 'Mark this class as an API Platform resource (expose a CRUD API for it)')
            ->addOption('broadcast', 'b', InputOption::VALUE_NONE, 'Add the ability to broadcast entity updates using Symfony UX Turbo?')
            ->addOption('regenerate', null, InputOption::VALUE_NONE, 'Instead of adding new fields, simply generate the methods (e.g. getter/setter) for existing fields')
            ->addOption('overwrite', null, InputOption::VALUE_NONE, 'Overwrite any existing getter/setter methods')
            ->setHelp(file_get_contents(__DIR__.'/../Resources/help/MakeEntity.txt'))
        ;

        $this->addWithUuidOption($command);

        $inputConfig->setArgumentAsNonInteractive('name');
    }

    public function interact(InputInterface $input, ConsoleStyle $io, Command $command): void
    {
        if (($entityClassName = $input->getArgument('name')) && empty($this->verifyEntityName($entityClassName))) {
            return;
        }

        if ($input->getOption('regenerate')) {
            $io->block([
                'This command will generate any missing methods (e.g. getters & setters) for a class or all classes in a namespace.',
                'To overwrite any existing methods, re-run this command with the --overwrite flag',
            ], null, 'fg=yellow');
            $classOrNamespace = $io->ask('Enter a class or namespace to regenerate', $this->getEntityNamespace(), Validator::notBlank(...));

            $input->setArgument('name', $classOrNamespace);

            return;
        }

        $this->checkIsUsingUid($input);

        $argument = $command->getDefinition()->getArgument('name');
        $question = $this->createEntityClassQuestion($argument->getDescription());
        $entityClassName ??= $io->askQuestion($question);

        while ($dangerous = $this->verifyEntityName($entityClassName)) {
            if ($io->confirm(\sprintf('"%s" contains one or more non-ASCII characters, which are potentially problematic with some database. It is recommended to use only ASCII characters for entity names. Continue anyway?', $entityClassName), false)) {
                break;
            }

            $entityClassName = $io->askQuestion($question);
        }

        $input->setArgument('name', $entityClassName);

        if (
            !$input->getOption('api-resource')
            && class_exists(ApiResource::class)
            && !class_exists($this->generator->createClassNameDetails($entityClassName, 'Entity\\')->getFullName())
        ) {
            $description = $command->getDefinition()->getOption('api-resource')->getDescription();
            $question = new ConfirmationQuestion($description, false);
            $isApiResource = $io->askQuestion($question);

            $input->setOption('api-resource', $isApiResource);
        }

        if (
            !$input->getOption('broadcast')
            && class_exists(Broadcast::class)
            && !class_exists($this->generator->createClassNameDetails($entityClassName, 'Entity\\')->getFullName())
        ) {
            $description = $command->getDefinition()->getOption('broadcast')->getDescription();
            $question = new ConfirmationQuestion($description, false);
            $isBroadcast = $io->askQuestion($question);

            // Mercure is needed
            if ($isBroadcast && !class_exists(MercureExtension::class)) {
                throw new RuntimeCommandException('Please run "composer require symfony/mercure-bundle". It is needed to broadcast entities.');
            }

            $input->setOption('broadcast', $isBroadcast);
        }
    }

    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator): void
    {
        $overwrite = $input->getOption('overwrite');

        // the regenerate option has entirely custom behavior
        if ($input->getOption('regenerate')) {
            $this->regenerateEntities($input->getArgument('name'), $overwrite, $generator);
            $this->writeSuccessMessage($io);

            return;
        }

        $entityClassDetails = $generator->createClassNameDetails(
            $input->getArgument('name'),
            'Entity\\'
        );

        $classExists = class_exists($entityClassDetails->getFullName());
        if (!$classExists) {
            $broadcast = $input->getOption('broadcast');
            $entityPath = $this->entityClassGenerator->generateEntityClass(
                entityClassDetails: $entityClassDetails,
                apiResource: $input->getOption('api-resource'),
                broadcast: $broadcast,
                useUuidIdentifier: $this->getIdType(),
            );

            if ($broadcast) {
                $shortName = $entityClassDetails->getShortName();
                $generator->generateTemplate(
                    \sprintf('broadcast/%s.stream.html.twig', $shortName),
                    'doctrine/broadcast_twig_template.tpl.php',
                    [
                        'class_name' => Str::asSnakeCase($shortName),
                        'class_name_plural' => Str::asSnakeCase(Str::singularCamelCaseToPluralCamelCase($shortName)),
                    ]
                );
            }

            $generator->writeChanges();
        }

        if ($classExists) {
            $entityPath = $this->getPathOfClass($entityClassDetails->getFullName());
            $io->text([
                'Your entity already exists! So let\'s add some new fields!',
            ]);
        } else {
            $io->text([
                '',
                'Entity generated! Now let\'s add some fields!',
                'You can always add more fields later manually or by re-running this command.',
            ]);
        }

        $currentFields = $this->getPropertyNames($entityClassDetails->getFullName());
        $manipulator = $this->createClassManipulator($entityPath, $io, $overwrite);

        $isFirstField = true;
        while (true) {
            $newField = $this->askForNextField($io, $currentFields, $entityClassDetails->getFullName(), $isFirstField);
            $isFirstField = false;

            if (null === $newField) {
                break;
            }

            $fileManagerOperations = [];
            $fileManagerOperations[$entityPath] = $manipulator;

            if ($newField instanceof ClassProperty) {
                $manipulator->addEntityField($newField);

                $currentFields[] = $newField->propertyName;
            } elseif ($newField instanceof EntityRelation) {
                // both overridden below for OneToMany
                $newFieldName = $newField->getOwningProperty();
                if ($newField->isSelfReferencing()) {
                    $otherManipulatorFilename = $entityPath;
                    $otherManipulator = $manipulator;
                } else {
                    $otherManipulatorFilename = $this->getPathOfClass($newField->getInverseClass());
                    $otherManipulator = $this->createClassManipulator($otherManipulatorFilename, $io, $overwrite);
                }
                switch ($newField->getType()) {
                    case EntityRelation::MANY_TO_ONE:
                        if ($newField->getOwningClass() === $entityClassDetails->getFullName()) {
                            // THIS class will receive the ManyToOne
                            $manipulator->addManyToOneRelation($newField->getOwningRelation());

                            if ($newField->getMapInverseRelation()) {
                                $otherManipulator->addOneToManyRelation($newField->getInverseRelation());
                            }
                        } else {
                            // the new field being added to THIS entity is the inverse
                            $newFieldName = $newField->getInverseProperty();
                            $otherManipulatorFilename = $this->getPathOfClass($newField->getOwningClass());
                            $otherManipulator = $this->createClassManipulator($otherManipulatorFilename, $io, $overwrite);

                            // The *other* class will receive the ManyToOne
                            $otherManipulator->addManyToOneRelation($newField->getOwningRelation());
                            if (!$newField->getMapInverseRelation()) {
                                throw new \Exception('Somehow a OneToMany relationship is being created, but the inverse side will not be mapped?');
                            }
                            $manipulator->addOneToManyRelation($newField->getInverseRelation());
                        }

                        break;
                    case EntityRelation::MANY_TO_MANY:
                        $manipulator->addManyToManyRelation($newField->getOwningRelation());
                        if ($newField->getMapInverseRelation()) {
                            $otherManipulator->addManyToManyRelation($newField->getInverseRelation());
                        }

                        break;
                    case EntityRelation::ONE_TO_ONE:
                        $manipulator->addOneToOneRelation($newField->getOwningRelation());
                        if ($newField->getMapInverseRelation()) {
                            $otherManipulator->addOneToOneRelation($newField->getInverseRelation());
                        }

                        break;
                    default:
                        throw new \Exception('Invalid relation type');
                }

                // save the inverse side if it's being mapped
                if ($newField->getMapInverseRelation()) {
                    $fileManagerOperations[$otherManipulatorFilename] = $otherManipulator;
                }
                $currentFields[] = $newFieldName;
            } else {
                throw new \Exception('Invalid value');
            }

            foreach ($fileManagerOperations as $path => $manipulatorOrMessage) {
                if (\is_string($manipulatorOrMessage)) {     /* @phpstan-ignore-line - https://github.com/symfony/maker-bundle/issues/1509 */
                    $io->comment($manipulatorOrMessage);
                } else {
                    $this->fileManager->dumpFile($path, $manipulatorOrMessage->getSourceCode());
                }
            }
        }

        $this->writeSuccessMessage($io);
        $io->text([
            \sprintf('Next: When you\'re ready, create a migration with <info>%s make:migration</info>', CliOutputHelper::getCommandPrefix()),
            '',
        ]);
    }

    public function configureDependencies(DependencyBuilder $dependencies, ?InputInterface $input = null): void
    {
        if (null !== $input && $input->getOption('api-resource')) {
            $dependencies->addClassDependency(
                ApiResource::class,
                'api'
            );
        }

        if (null !== $input && $input->getOption('broadcast')) {
            $dependencies->addClassDependency(
                Broadcast::class,
                'symfony/ux-turbo'
            );
        }

        ORMDependencyBuilder::buildDependencies($dependencies);
    }

    /** @param string[] $fields */
    private function askForNextField(ConsoleStyle $io, array $fields, string $entityClass, bool $isFirstField): EntityRelation|ClassProperty|null
    {
        $io->writeln('');

        if ($isFirstField) {
            $questionText = 'New property name (press <return> to stop adding fields)';
        } else {
            $questionText = 'Add another property? Enter the property name (or press <return> to stop adding fields)';
        }

        $fieldName = $io->ask($questionText, null, function ($name) use ($fields) {
            // allow it to be empty
            if (!$name) {
                return $name;
            }

            if (\in_array($name, $fields)) {
                throw new \InvalidArgumentException(\sprintf('The "%s" property already exists.', $name));
            }

            return Validator::validateDoctrineFieldName($name, $this->doctrineHelper->getRegistry());
        });

        if (!$fieldName) {
            return null;
        }

        $defaultType = 'string';
        // try to guess the type by the field name prefix/suffix
        // convert to snake case for simplicity
        $snakeCasedField = Str::asSnakeCase($fieldName);

        if ('_at' === $suffix = substr($snakeCasedField, -3)) {
            $defaultType = 'datetime_immutable';
        } elseif ('_id' === $suffix) {
            $defaultType = 'integer';
        } elseif (str_starts_with($snakeCasedField, 'is_')) {
            $defaultType = 'boolean';
        } elseif (str_starts_with($snakeCasedField, 'has_')) {
            $defaultType = 'boolean';
        } elseif ('uuid' === $snakeCasedField) {
            $defaultType = Type::hasType('uuid') ? 'uuid' : 'guid';
        } elseif ('guid' === $snakeCasedField) {
            $defaultType = 'guid';
        }

        $type = null;
        $types = $this->getTypesMap();

        $allValidTypes = array_merge(
            array_keys($types),
            EntityRelation::getValidRelationTypes(),
            ['relation', 'enum']
        );
        while (null === $type) {
            $question = new Question('Field type (enter <comment>?</comment> to see all types)', $defaultType);
            $question->setAutocompleterValues($allValidTypes);
            $type = $io->askQuestion($question);

            if ('?' === $type) {
                $this->printAvailableTypes($io);
                $io->writeln('');

                $type = null;
            } elseif (!\in_array($type, $allValidTypes)) {
                $this->printAvailableTypes($io);
                $io->error(\sprintf('Invalid type "%s".', $type));
                $io->writeln('');

                $type = null;
            }
        }

        if ('relation' === $type || \in_array($type, EntityRelation::getValidRelationTypes())) {
            return $this->askRelationDetails($io, $entityClass, $type, $fieldName);
        }

        // this is a normal field
        $classProperty = new ClassProperty(propertyName: $fieldName, type: $type);

        if ('string' === $type) {
            // default to 255, avoid the question
            $classProperty->length = $io->ask('Field length', '255', Validator::validateLength(...));
        } elseif ('decimal' === $type) {
            // 10 is the default value given in \Doctrine\DBAL\Schema\Column::$_precision
            $classProperty->precision = $io->ask('Precision (total number of digits stored: 100.00 would be 5)', '10', Validator::validatePrecision(...));

            // 0 is the default value given in \Doctrine\DBAL\Schema\Column::$_scale
            $classProperty->scale = $io->ask('Scale (number of decimals to store: 100.00 would be 2)', '0', Validator::validateScale(...));
        } elseif ('enum' === $type) {
            // ask for valid backed enum class
            $classProperty->enumType = $io->ask('Enum class', null, Validator::classIsBackedEnum(...));

            // set type according to user decision
            $classProperty->type = $io->confirm('Can this field store multiple enum values', false) ? 'simple_array' : 'string';
        }

        if ($io->confirm('Can this field be null in the database (nullable)', false)) {
            $classProperty->nullable = true;
        }

        return $classProperty;
    }

    private function printAvailableTypes(ConsoleStyle $io): void
    {
        $allTypes = $this->getTypesMap();

        $typesTable = [
            'main' => [
                'string' => ['ascii_string'],
                'text' => [],
                'boolean' => [],
                'integer' => ['smallint', 'bigint'],
                'float' => [],
            ],
            'array_object' => [
                'array' => ['simple_array'],
                'json' => [],
                'object' => [],
                'binary' => [],
                'blob' => [],
            ],
            'date_time' => [
                'datetime' => ['datetime_immutable'],
                'datetimetz' => ['datetimetz_immutable'],
                'date' => ['date_immutable'],
                'time' => ['time_immutable'],
                'dateinterval' => [],
            ],
            'other' => [
                'enum' => [],
            ],
        ];

        $printSection = static function (array $sectionTypes) use ($io, &$allTypes) {
            foreach ($sectionTypes as $mainType => $subTypes) {
                if (!\array_key_exists($mainType, $allTypes)) {
                    // The type is not a valid DBAL Type - don't show it as an option
                    continue;
                }

                foreach ($subTypes as $key => $potentialType) {
                    if (!\array_key_exists($potentialType, $allTypes)) {
                        // The type is not a valid DBAL Type - don't show it as an "or" option
                        unset($subTypes[$key]);
                    }

                    // Remove type as not to show it again in "Other Types"
                    unset($allTypes[$potentialType]);
                }

                // Remove type as not to show it again in "Other Types"
                unset($allTypes[$mainType]);

                $line = \sprintf('  * <comment>%s</comment>', $mainType);

                if (!empty($subTypes)) {
                    $line .= \sprintf(' or %s', implode(' or ', array_map(
                        static fn ($subType) => \sprintf('<comment>%s</comment>', $subType), $subTypes))
                    );
                }

                $io->writeln($line);
            }

            $io->writeln('');
        };

        $printRelationsSection = static function () use ($io) {
            if ('Hyper' === getenv('TERM_PROGRAM')) {
                $wizard = 'wizard 🧙';
            } else {
                $wizard = '\\' === \DIRECTORY_SEPARATOR ? 'wizard' : 'wizard 🧙';
            }

            $io->writeln(\sprintf('  * <comment>relation</comment> a %s will help you build the relation', $wizard));

            $relations = [EntityRelation::MANY_TO_ONE, EntityRelation::ONE_TO_MANY, EntityRelation::MANY_TO_MANY, EntityRelation::ONE_TO_ONE];
            foreach ($relations as $relation) {
                $line = \sprintf('  * <comment>%s</comment>', $relation);

                $io->writeln($line);
            }

            $io->writeln('');
        };

        $io->writeln('<info>Main Types</info>');
        $printSection($typesTable['main']);

        $io->writeln('<info>Relationships/Associations</info>');
        $printRelationsSection();

        $io->writeln('<info>Array/Object Types</info>');
        $printSection($typesTable['array_object']);

        $io->writeln('<info>Date/Time Types</info>');
        $printSection($typesTable['date_time']);

        $io->writeln('<info>Other Types</info>');
        // empty the values
        $allTypes = array_map(static fn () => [], $allTypes);
        $allTypes = [...$typesTable['other'], ...$allTypes];
        $printSection($allTypes);
    }

    private function createEntityClassQuestion(string $questionText): Question
    {
        $question = new Question($questionText);
        $question->setValidator(Validator::notBlank(...));
        $question->setAutocompleterValues($this->doctrineHelper->getEntitiesForAutocomplete());

        return $question;
    }

    private function askRelationDetails(ConsoleStyle $io, string $generatedEntityClass, string $type, string $newFieldName): EntityRelation
    {
        // ask the targetEntity
        $targetEntityClass = null;
        while (null === $targetEntityClass) {
            $question = $this->createEntityClassQuestion('What class should this entity be related to?');

            $answeredEntityClass = $io->askQuestion($question);

            // find the correct class name - but give priority over looking
            // in the Entity namespace versus just checking the full class
            // name to avoid issues with classes like "Directory" that exist
            // in PHP's core.
            if (class_exists($this->getEntityNamespace().'\\'.$answeredEntityClass)) {
                $targetEntityClass = $this->getEntityNamespace().'\\'.$answeredEntityClass;
            } elseif (class_exists($answeredEntityClass)) {
                $targetEntityClass = $answeredEntityClass;
            } else {
                $io->error(\sprintf('Unknown class "%s"', $answeredEntityClass));
            }
        }

        // help the user select the type
        if ('relation' === $type) {
            $type = $this->askRelationType($io, $generatedEntityClass, $targetEntityClass);
        }

        $askFieldName = fn (string $targetClass, string $defaultValue) => $io->ask(
            \sprintf('New field name inside %s', Str::getShortClassName($targetClass)),
            $defaultValue,
            function ($name) use ($targetClass) {
                // it's still *possible* to create duplicate properties - by
                // trying to generate the same property 2 times during the
                // same make:entity run. property_exists() only knows about
                // properties that *originally* existed on this class.
                if (property_exists($targetClass, $name)) {
                    throw new \InvalidArgumentException(\sprintf('The "%s" class already has a "%s" property.', $targetClass, $name));
                }

                return Validator::validateDoctrineFieldName($name, $this->doctrineHelper->getRegistry());
            }
        );

        $askIsNullable = static fn (string $propertyName, string $targetClass) => $io->confirm(\sprintf(
            'Is the <comment>%s</comment>.<comment>%s</comment> property allowed to be null (nullable)?',
            Str::getShortClassName($targetClass),
            $propertyName
        ));

        $askOrphanRemoval = static function (string $owningClass, string $inverseClass) use ($io) {
            $io->text([
                'Do you want to activate <comment>orphanRemoval</comment> on your relationship?',
                \sprintf(
                    'A <comment>%s</comment> is "orphaned" when it is removed from its related <comment>%s</comment>.',
                    Str::getShortClassName($owningClass),
                    Str::getShortClassName($inverseClass)
                ),
                \sprintf(
                    'e.g. <comment>$%s->remove%s($%s)</comment>',
                    Str::asLowerCamelCase(Str::getShortClassName($inverseClass)),
                    Str::asCamelCase(Str::getShortClassName($owningClass)),
                    Str::asLowerCamelCase(Str::getShortClassName($owningClass))
                ),
                '',
                \sprintf(
                    'NOTE: If a <comment>%s</comment> may *change* from one <comment>%s</comment> to another, answer "no".',
                    Str::getShortClassName($owningClass),
                    Str::getShortClassName($inverseClass)
                ),
            ]);

            return $io->confirm(\sprintf('Do you want to automatically delete orphaned <comment>%s</comment> objects (orphanRemoval)?', $owningClass), false);
        };

        $askInverseSide = function (EntityRelation $relation) use ($io) {
            if ($this->isClassInVendor($relation->getInverseClass())) {
                $relation->setMapInverseRelation(false);

                return;
            }

            // recommend an inverse side, except for OneToOne, where it's inefficient
            $recommendMappingInverse = EntityRelation::ONE_TO_ONE !== $relation->getType();

            $getterMethodName = 'get'.Str::asCamelCase(Str::getShortClassName($relation->getOwningClass()));
            if (EntityRelation::ONE_TO_ONE !== $relation->getType()) {
                // pluralize!
                $getterMethodName = Str::singularCamelCaseToPluralCamelCase($getterMethodName);
            }
            $mapInverse = $io->confirm(
                \sprintf(
                    'Do you want to add a new property to <comment>%s</comment> so that you can access/update <comment>%s</comment> objects from it - e.g. <comment>$%s->%s()</comment>?',
                    Str::getShortClassName($relation->getInverseClass()),
                    Str::getShortClassName($relation->getOwningClass()),
                    Str::asLowerCamelCase(Str::getShortClassName($relation->getInverseClass())),
                    $getterMethodName
                ),
                $recommendMappingInverse
            );
            $relation->setMapInverseRelation($mapInverse);
        };

        switch ($type) {
            case EntityRelation::MANY_TO_ONE:
                $relation = new EntityRelation(
                    EntityRelation::MANY_TO_ONE,
                    $generatedEntityClass,
                    $targetEntityClass
                );
                $relation->setOwningProperty($newFieldName);

                $relation->setIsNullable($askIsNullable(
                    $relation->getOwningProperty(),
                    $relation->getOwningClass()
                ));

                $askInverseSide($relation);
                if ($relation->getMapInverseRelation()) {
                    $io->comment(\sprintf(
                        'A new property will also be added to the <comment>%s</comment> class so that you can access the related <comment>%s</comment> objects from it.',
                        Str::getShortClassName($relation->getInverseClass()),
                        Str::getShortClassName($relation->getOwningClass())
                    ));
                    $relation->setInverseProperty($askFieldName(
                        $relation->getInverseClass(),
                        Str::singularCamelCaseToPluralCamelCase(Str::getShortClassName($relation->getOwningClass()))
                    ));

                    // orphan removal only applies if the inverse relation is set
                    if (!$relation->isNullable()) {
                        $relation->setOrphanRemoval($askOrphanRemoval(
                            $relation->getOwningClass(),
                            $relation->getInverseClass()
                        ));
                    }
                }

                break;
            case EntityRelation::ONE_TO_MANY:
                // we *actually* create a ManyToOne, but populate it differently
                $relation = new EntityRelation(
                    EntityRelation::MANY_TO_ONE,
                    $targetEntityClass,
                    $generatedEntityClass
                );
                $relation->setInverseProperty($newFieldName);

                $io->comment(\sprintf(
                    'A new property will also be added to the <comment>%s</comment> class so that you can access and set the related <comment>%s</comment> object from it.',
                    Str::getShortClassName($relation->getOwningClass()),
                    Str::getShortClassName($relation->getInverseClass())
                ));
                $relation->setOwningProperty($askFieldName(
                    $relation->getOwningClass(),
                    Str::asLowerCamelCase(Str::getShortClassName($relation->getInverseClass()))
                ));

                $relation->setIsNullable($askIsNullable(
                    $relation->getOwningProperty(),
                    $relation->getOwningClass()
                ));

                if (!$relation->isNullable()) {
                    $relation->setOrphanRemoval($askOrphanRemoval(
                        $relation->getOwningClass(),
                        $relation->getInverseClass()
                    ));
                }

                break;
            case EntityRelation::MANY_TO_MANY:
                $relation = new EntityRelation(
                    EntityRelation::MANY_TO_MANY,
                    $generatedEntityClass,
                    $targetEntityClass
                );
                $relation->setOwningProperty($newFieldName);

                $askInverseSide($relation);
                if ($relation->getMapInverseRelation()) {
                    $io->comment(\sprintf(
                        'A new property will also be added to the <comment>%s</comment> class so that you can access the related <comment>%s</comment> objects from it.',
                        Str::getShortClassName($relation->getInverseClass()),
                        Str::getShortClassName($relation->getOwningClass())
                    ));
                    $relation->setInverseProperty($askFieldName(
                        $relation->getInverseClass(),
                        Str::singularCamelCaseToPluralCamelCase(Str::getShortClassName($relation->getOwningClass()))
                    ));
                }

                break;
            case EntityRelation::ONE_TO_ONE:
                $relation = new EntityRelation(
                    EntityRelation::ONE_TO_ONE,
                    $generatedEntityClass,
                    $targetEntityClass
                );
                $relation->setOwningProperty($newFieldName);

                $relation->setIsNullable($askIsNullable(
                    $relation->getOwningProperty(),
                    $relation->getOwningClass()
                ));

                $askInverseSide($relation);
                if ($relation->getMapInverseRelation()) {
                    $io->comment(\sprintf(
                        'A new property will also be added to the <comment>%s</comment> class so that you can access the related <comment>%s</comment> object from it.',
                        Str::getShortClassName($relation->getInverseClass()),
                        Str::getShortClassName($relation->getOwningClass())
                    ));
                    $relation->setInverseProperty($askFieldName(
                        $relation->getInverseClass(),
                        Str::asLowerCamelCase(Str::getShortClassName($relation->getOwningClass()))
                    ));
                }

                break;
            default:
                throw new \InvalidArgumentException('Invalid type: '.$type);
        }

        return $relation;
    }

    private function askRelationType(ConsoleStyle $io, string $entityClass, string $targetEntityClass): string
    {
        $io->writeln('What type of relationship is this?');

        $originalEntityShort = Str::getShortClassName($entityClass);
        $targetEntityShort = Str::getShortClassName($targetEntityClass);
        if ($originalEntityShort === $targetEntityShort) {
            [$originalDiscriminator, $targetDiscriminator] = Str::getHumanDiscriminatorBetweenTwoClasses($entityClass, $targetEntityClass);
            $originalEntityShort = trim($originalDiscriminator.'\\'.$originalEntityShort, '\\');
            $targetEntityShort = trim($targetDiscriminator.'\\'.$targetEntityShort, '\\');
        }

        $rows = [];
        $rows[] = [
            EntityRelation::MANY_TO_ONE,
            \sprintf("Each <comment>%s</comment> relates to (has) <info>one</info> <comment>%s</comment>.\nEach <comment>%s</comment> can relate to (can have) <info>many</info> <comment>%s</comment> objects.", $originalEntityShort, $targetEntityShort, $targetEntityShort, $originalEntityShort),
        ];
        $rows[] = ['', ''];
        $rows[] = [
            EntityRelation::ONE_TO_MANY,
            \sprintf("Each <comment>%s</comment> can relate to (can have) <info>many</info> <comment>%s</comment> objects.\nEach <comment>%s</comment> relates to (has) <info>one</info> <comment>%s</comment>.", $originalEntityShort, $targetEntityShort, $targetEntityShort, $originalEntityShort),
        ];
        $rows[] = ['', ''];
        $rows[] = [
            EntityRelation::MANY_TO_MANY,
            \sprintf("Each <comment>%s</comment> can relate to (can have) <info>many</info> <comment>%s</comment> objects.\nEach <comment>%s</comment> can also relate to (can also have) <info>many</info> <comment>%s</comment> objects.", $originalEntityShort, $targetEntityShort, $targetEntityShort, $originalEntityShort),
        ];
        $rows[] = ['', ''];
        $rows[] = [
            EntityRelation::ONE_TO_ONE,
            \sprintf("Each <comment>%s</comment> relates to (has) exactly <info>one</info> <comment>%s</comment>.\nEach <comment>%s</comment> also relates to (has) exactly <info>one</info> <comment>%s</comment>.", $originalEntityShort, $targetEntityShort, $targetEntityShort, $originalEntityShort),
        ];

        $io->table([
            'Type',
            'Description',
        ], $rows);

        $question = new Question(\sprintf(
            'Relation type? [%s]',
            implode(', ', EntityRelation::getValidRelationTypes())
        ));
        $question->setAutocompleterValues(EntityRelation::getValidRelationTypes());
        $question->setValidator(function ($type) {
            if (!\in_array($type, EntityRelation::getValidRelationTypes())) {
                throw new \InvalidArgumentException(\sprintf('Invalid type: use one of: %s', implode(', ', EntityRelation::getValidRelationTypes())));
            }

            return $type;
        });

        return $io->askQuestion($question);
    }

    /** @return string[] */
    private function verifyEntityName(string $entityName): array
    {
        preg_match('/([^\x00-\x7F]+)/u', $entityName, $matches);

        return $matches;
    }

    private function createClassManipulator(string $path, ConsoleStyle $io, bool $overwrite): ClassSourceManipulator
    {
        $manipulator = new ClassSourceManipulator(
            sourceCode: $this->fileManager->getFileContents($path),
            overwrite: $overwrite,
        );

        $manipulator->setIo($io);

        return $manipulator;
    }

    private function getPathOfClass(string $class): string
    {
        return (new ClassDetails($class))->getPath();
    }

    private function isClassInVendor(string $class): bool
    {
        $path = $this->getPathOfClass($class);

        return $this->fileManager->isPathInVendor($path);
    }

    private function regenerateEntities(string $classOrNamespace, bool $overwrite, Generator $generator): void
    {
        $regenerator = new EntityRegenerator($this->doctrineHelper, $this->fileManager, $generator, $this->entityClassGenerator, $overwrite);
        $regenerator->regenerateEntities($classOrNamespace);
    }

    /** @return string[] */
    private function getPropertyNames(string $class): array
    {
        if (!class_exists($class)) {
            return [];
        }

        $reflClass = new \ReflectionClass($class);

        return array_map(static fn (\ReflectionProperty $prop) => $prop->getName(), $reflClass->getProperties());
    }

    private function getEntityNamespace(): string
    {
        return $this->doctrineHelper->getEntityNamespace();
    }

    /** @return string[] */
    private function getTypesMap(): array
    {
        return Type::getTypesMap();
    }
}
