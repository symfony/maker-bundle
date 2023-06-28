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

use ApiPlatform\Core\Annotation\ApiResource as LegacyApiResource;
use ApiPlatform\Metadata\ApiResource;

use Doctrine\ODM\MongoDB\Types\Type;
use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\DependencyBuilder;
use Symfony\Bundle\MakerBundle\Doctrine\DoctrineODMHelper;
use Symfony\Bundle\MakerBundle\Doctrine\DocumentClassGenerator;
use Symfony\Bundle\MakerBundle\Doctrine\DocumentRegenerator;
use Symfony\Bundle\MakerBundle\Doctrine\DocumentRelation;
use Symfony\Bundle\MakerBundle\Doctrine\ODMDependencyBuilder;
use Symfony\Bundle\MakerBundle\Exception\RuntimeCommandException;
use Symfony\Bundle\MakerBundle\FileManager;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\InputAwareMakerInterface;
use Symfony\Bundle\MakerBundle\InputConfiguration;
use Symfony\Bundle\MakerBundle\Str;
use Symfony\Bundle\MakerBundle\Util\ClassDetails;
use Symfony\Bundle\MakerBundle\Util\ClassSourceManipulator;
use Symfony\Bundle\MakerBundle\Util\CliOutputHelper;
use Symfony\Bundle\MakerBundle\Util\PhpCompatUtil;
use Symfony\Bundle\MakerBundle\Validator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 * @author Ryan Weaver <weaverryan@gmail.com>
 * @author Kévin Dunglas <dunglas@gmail.com>
 * @author Konstantin Chigakov <constantable@gmail.com>
 */
final class MakeDocument extends AbstractMaker implements InputAwareMakerInterface
{
    private Generator $generator;
    private DocumentClassGenerator $documentClassGenerator;
    private PhpCompatUtil $phpCompatUtil;

    public function __construct(
        private FileManager $fileManager,
        private DoctrineODMHelper $doctrineHelper,
        string $projectDirectory = null,
        Generator $generator = null,
        DocumentClassGenerator $documentClassGenerator = null,
        PhpCompatUtil $phpCompatUtil = null,
    ) {
        if (null !== $projectDirectory) {
            @trigger_error('The $projectDirectory constructor argument is no longer used', \E_USER_DEPRECATED);
        }

        if (null === $generator) {
            @trigger_error(sprintf('Passing a "%s" instance as 4th argument is mandatory', Generator::class), \E_USER_DEPRECATED);
            $this->generator = new Generator($fileManager, 'App\\');
        } else {
            $this->generator = $generator;
        }

        if (null === $documentClassGenerator) {
            @trigger_error(sprintf('Passing a "%s" instance as 5th argument is mandatory', DocumentClassGenerator::class), \E_USER_DEPRECATED);
            $this->documentClassGenerator = new DocumentClassGenerator($generator, $this->doctrineHelper);
        } else {
            $this->documentClassGenerator = $documentClassGenerator;
        }

        if (null === $phpCompatUtil) {
            @trigger_error(sprintf('Passing a "%s" instance as 6th argument is mandatory', PhpCompatUtil::class), \E_USER_DEPRECATED);
            $this->phpCompatUtil = new PhpCompatUtil($this->fileManager);
        } else {
            $this->phpCompatUtil = $phpCompatUtil;
        }
    }

    public static function getCommandName(): string
    {
        return 'make:document';
    }

    public static function getCommandDescription(): string
    {
        return 'Creates or updates a Doctrine ODM document class, and optionally an API Platform resource';
    }

    public function configureCommand(Command $command, InputConfiguration $inputConfig): void
    {
        $command
            ->addArgument('name', InputArgument::OPTIONAL, sprintf('Class name of the document to create or update (e.g. <fg=yellow>%s</>)', Str::asClassName(Str::getRandomTerm())))
            ->addOption('api-resource', 'a', InputOption::VALUE_NONE, 'Mark this class as an API Platform resource (expose a CRUD API for it)')
            ->addOption('regenerate', null, InputOption::VALUE_NONE, 'Instead of adding new fields, simply generate the methods (e.g. getter/setter) for existing fields')
            ->addOption('overwrite', null, InputOption::VALUE_NONE, 'Overwrite any existing getter/setter methods')
            ->setHelp(file_get_contents(__DIR__.'/../Resources/help/MakeDocument.txt'))
        ;

        $inputConfig->setArgumentAsNonInteractive('name');
    }

    public function interact(InputInterface $input, ConsoleStyle $io, Command $command): void
    {
        if ($input->getArgument('name')) {
            return;
        }

        if ($input->getOption('regenerate')) {
            $io->block([
                'This command will generate any missing methods (e.g. getters & setters) for a class or all classes in a namespace.',
                'To overwrite any existing methods, re-run this command with the --overwrite flag',
            ], null, 'fg=yellow');
            $classOrNamespace = $io->ask('Enter a class or namespace to regenerate', $this->getDocumentNamespace(), [Validator::class, 'notBlank']);

            $input->setArgument('name', $classOrNamespace);

            return;
        }

        $argument = $command->getDefinition()->getArgument('name');
        $question = $this->createDocumentClassQuestion($argument->getDescription());
        $documentClassName = $io->askQuestion($question);

        $input->setArgument('name', $documentClassName);

        if (
            !$input->getOption('api-resource')
            && (class_exists(ApiResource::class) || class_exists(LegacyApiResource::class))
            && !class_exists($this->generator->createClassNameDetails($documentClassName, 'Document\\')->getFullName())
        ) {
            $description = $command->getDefinition()->getOption('api-resource')->getDescription();
            $question = new ConfirmationQuestion($description, false);
            $isApiResource = $io->askQuestion($question);

            $input->setOption('api-resource', $isApiResource);
        }
    }

    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator): void
    {
        $overwrite = $input->getOption('overwrite');

        // the regenerate option has entirely custom behavior
        if ($input->getOption('regenerate')) {
            $this->regenerateDocuments($input->getArgument('name'), $overwrite, $generator);
            $this->writeSuccessMessage($io);

            return;
        }

        $documentClassDetails = $generator->createClassNameDetails(
            $input->getArgument('name'),
            'Document\\'
        );

        $classExists = class_exists($documentClassDetails->getFullName());
        if (!$classExists) {
            $documentPath = $this->documentClassGenerator->generateDocumentClass(
                $documentClassDetails,
                $input->getOption('api-resource'),
                true
            );

            $generator->writeChanges();
        }

        if (!$this->doesDocumentUseAttributeMapping($documentClassDetails->getFullName())) {
            throw new RuntimeCommandException(sprintf('Only attribute mapping is supported by make:document, but the <info>%s</info> class uses a different format. If you would like this command to generate the properties & getter/setter methods, add your mapping configuration, and then re-run this command with the <info>--regenerate</info> flag.', $documentClassDetails->getFullName()));
        }

        if ($classExists) {
            $documentPath = $this->getPathOfClass($documentClassDetails->getFullName());
            $io->text([
                'Your document already exists! So let\'s add some new fields!',
            ]);
        } else {
            $io->text([
                '',
                'Document generated! Now let\'s add some fields!',
                'You can always add more fields later manually or by re-running this command.',
            ]);
        }

        $currentFields = $this->getPropertyNames($documentClassDetails->getFullName());
        $manipulator = $this->createClassManipulator($documentPath, $io, $overwrite);

        $isFirstField = true;
        while (true) {
            $newField = $this->askForNextField($io, $currentFields, $documentClassDetails->getFullName(), $isFirstField);
            $isFirstField = false;

            if (null === $newField) {
                break;
            }

            $fileManagerOperations = [];
            $fileManagerOperations[$documentPath] = $manipulator;

            if (\is_array($newField)) {
                $annotationOptions = $newField;
                unset($annotationOptions['fieldName']);
                $manipulator->addDocumentField($newField['fieldName'], $annotationOptions);

                $currentFields[] = $newField['fieldName'];
            } elseif ($newField instanceof DocumentRelation) {
                // both overridden below for OneToMany
                $newFieldName = $newField->getOwningProperty();
                if ($newField->isSelfReferencing()) {
                    $otherManipulatorFilename = $documentPath;
                    $otherManipulator = $manipulator;
                } else {
                    $otherManipulatorFilename = $this->getPathOfClass($newField->getInverseClass());
                    $otherManipulator = $this->createClassManipulator($otherManipulatorFilename, $io, $overwrite);
                }
                switch ($newField->getType()) {
                    case DocumentRelation::REFERENCE_ONE:
                        if ($newField->getOwningClass() === $documentClassDetails->getFullName()) {
                            // THIS class will receive the ManyToOne
                            $manipulator->addManyToOneReference($newField->getOwningRelation());

                            if ($newField->getMapInverseRelation()) {
                                $otherManipulator->addOneToManyReference($newField->getInverseRelation());
                            }
                        } else {
                            // the new field being added to THIS document is the inverse
                            $newFieldName = $newField->getInverseProperty();
                            $otherManipulatorFilename = $this->getPathOfClass($newField->getOwningClass());
                            $otherManipulator = $this->createClassManipulator($otherManipulatorFilename, $io, $overwrite);

                            // The *other* class will receive the ManyToOne
                            $otherManipulator->addManyToOneReference($newField->getOwningRelation());
                            if (!$newField->getMapInverseRelation()) {
                                throw new \Exception('Somehow a ReferenceOne relationship is being created, but the inverse side will not be mapped?');
                            }
                            $manipulator->addOneToManyReference($newField->getInverseRelation());
                        }

                        break;
                    case DocumentRelation::MANY_TO_MANY:
                        $manipulator->addManyToManyReference($newField->getOwningRelation());
                        if ($newField->getMapInverseRelation()) {
                            $otherManipulator->addManyToManyReference($newField->getInverseRelation());
                        }

                        break;
                    case DocumentRelation::ONE_TO_ONE:
                        $manipulator->addOneToOneReference($newField->getOwningRelation());
                        if ($newField->getMapInverseRelation()) {
                            $otherManipulator->addOneToOneReference($newField->getInverseRelation());
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
                if (\is_string($manipulatorOrMessage)) {
                    $io->comment($manipulatorOrMessage);
                } else {
                    $this->fileManager->dumpFile($path, $manipulatorOrMessage->getSourceCode());
                }
            }
        }

        $this->writeSuccessMessage($io);
    }

    public function configureDependencies(DependencyBuilder $dependencies, InputInterface $input = null): void
    {
        if (null !== $input && $input->getOption('api-resource')) {
            if (class_exists(ApiResource::class)) {
                $dependencies->addClassDependency(
                    ApiResource::class,
                    'api'
                );
            } else {
                $dependencies->addClassDependency(
                    LegacyApiResource::class,
                    'api'
                );
            }
        }

        ODMDependencyBuilder::buildDependencies($dependencies);
    }

    private function askForNextField(ConsoleStyle $io, array $fields, string $documentClass, bool $isFirstField): DocumentRelation|array|null
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
                throw new \InvalidArgumentException(sprintf('The "%s" property already exists.', $name));
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
            $defaultType = 'date_immutable';
        } elseif ('_id' === $suffix) {
            $defaultType = 'int';
        } elseif (str_starts_with($snakeCasedField, 'is_')) {
            $defaultType = 'bool';
        } elseif (str_starts_with($snakeCasedField, 'has_')) {
            $defaultType = 'bool';
        } elseif ('uuid' === $snakeCasedField) {
            $defaultType = 'bin_uuid';
        }

        $type = null;
        $types = $this->getTypesMap();

        $allValidTypes = array_merge(
            array_keys($types),
            DocumentRelation::getValidRelationTypes(),
            ['relation']
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
                $io->error(sprintf('Invalid type "%s".', $type));
                $io->writeln('');

                $type = null;
            }
        }

        if ('relation' === $type || \in_array($type, DocumentRelation::getValidRelationTypes())) {
            return $this->askRelationDetails($io, $documentClass, $type, $fieldName);
        }

        // this is a normal field
        $data = ['fieldName' => $fieldName, 'type' => $type];

        if ($io->confirm('Can this field be null in the database (nullable)', false)) {
            $data['nullable'] = true;
        }

        return $data;
    }

    private function printAvailableTypes(ConsoleStyle $io): void
    {
        $allTypes = $this->getTypesMap();

        if ('Hyper' === getenv('TERM_PROGRAM')) {
            $wizard = 'wizard 🧙';
        } else {
            $wizard = '\\' === \DIRECTORY_SEPARATOR ? 'wizard' : 'wizard 🧙';
        }

        $typesTable = [
            'main' => [
                'string' => [],
                'bool' => [],
                'int' => [],
                'float' => [],
            ],
            'relation' => [
                'relation' => 'a '.$wizard.' will help you build the relation',
                DocumentRelation::REFERENCE_ONE => [],
                DocumentRelation::REFERENCE_MANY => [],
            ],
            'array_object' => [
                'hash' => [],
                'collection' => [],
            ],
            'date_time' => [
                'date_immutable' => [],
                'date' => [],
                'timestamp' => [],
            ],
        ];

        $printSection = static function (array $sectionTypes) use ($io, &$allTypes) {
            foreach ($sectionTypes as $mainType => $subTypes) {
                unset($allTypes[$mainType]);
                $line = sprintf('  * <comment>%s</comment>', $mainType);

                if (\is_string($subTypes) && $subTypes) {
                    $line .= sprintf(' (%s)', $subTypes);
                } elseif (\is_array($subTypes) && !empty($subTypes)) {
                    $line .= sprintf(' (or %s)', implode(', ', array_map(
                        static fn ($subType) => sprintf('<comment>%s</comment>', $subType), $subTypes))
                    );

                    foreach ($subTypes as $subType) {
                        unset($allTypes[$subType]);
                    }
                }

                $io->writeln($line);
            }

            $io->writeln('');
        };

        $io->writeln('<info>Main Types</info>');
        $printSection($typesTable['main']);

        $io->writeln('<info>Relationships/Associations</info>');
        $printSection($typesTable['relation']);

        $io->writeln('<info>Array/Object Types</info>');
        $printSection($typesTable['array_object']);

        $io->writeln('<info>Date/Time Types</info>');
        $printSection($typesTable['date_time']);

        $io->writeln('<info>Other Types</info>');
        // empty the values
        $allTypes = array_map(static fn () => [], $allTypes);
        $printSection($allTypes);
    }

    private function createDocumentClassQuestion(string $questionText): Question
    {
        $question = new Question($questionText);
        $question->setValidator([Validator::class, 'notBlank']);
        $question->setAutocompleterValues($this->doctrineHelper->getDocumentsForAutocomplete());

        return $question;
    }

    private function askRelationDetails(ConsoleStyle $io, string $generatedDocumentClass, string $type, string $newFieldName): DocumentRelation
    {
        // ask the targetDocument
        $targetDocumentClass = null;
        while (null === $targetDocumentClass) {
            $question = $this->createDocumentClassQuestion('What class should this document be related to?');

            $answeredDocumentClass = $io->askQuestion($question);

            // find the correct class name - but give priority over looking
            // in the Document namespace versus just checking the full class
            // name to avoid issues with classes like "Directory" that exist
            // in PHP's core.
            if (class_exists($this->getDocumentNamespace().'\\'.$answeredDocumentClass)) {
                $targetDocumentClass = $this->getDocumentNamespace().'\\'.$answeredDocumentClass;
            } elseif (class_exists($answeredDocumentClass)) {
                $targetDocumentClass = $answeredDocumentClass;
            } else {
                $io->error(sprintf('Unknown class "%s"', $answeredDocumentClass));
                continue;
            }
        }

        // help the user select the type
        if ('relation' === $type) {
            $type = $this->askRelationType($io, $generatedDocumentClass, $targetDocumentClass);
        }

        $askFieldName = fn (string $targetClass, string $defaultValue) => $io->ask(
            sprintf('New field name inside %s', Str::getShortClassName($targetClass)),
            $defaultValue,
            function ($name) use ($targetClass) {
                // it's still *possible* to create duplicate properties - by
                // trying to generate the same property 2 times during the
                // same make:document run. property_exists() only knows about
                // properties that *originally* existed on this class.
                if (property_exists($targetClass, $name)) {
                    throw new \InvalidArgumentException(sprintf('The "%s" class already has a "%s" property.', $targetClass, $name));
                }

                return Validator::validateDoctrineFieldName($name, $this->doctrineHelper->getRegistry());
            }
        );

        $askIsNullable = static fn (string $propertyName, string $targetClass) => $io->confirm(sprintf(
            'Is the <comment>%s</comment>.<comment>%s</comment> property allowed to be null (nullable)?',
            Str::getShortClassName($targetClass),
            $propertyName
        ));

        $askOrphanRemoval = static function (string $owningClass, string $inverseClass) use ($io) {
            $io->text([
                'Do you want to activate <comment>orphanRemoval</comment> on your relationship?',
                sprintf(
                    'A <comment>%s</comment> is "orphaned" when it is removed from its related <comment>%s</comment>.',
                    Str::getShortClassName($owningClass),
                    Str::getShortClassName($inverseClass)
                ),
                sprintf(
                    'e.g. <comment>$%s->remove%s($%s)</comment>',
                    Str::asLowerCamelCase(Str::getShortClassName($inverseClass)),
                    Str::asCamelCase(Str::getShortClassName($owningClass)),
                    Str::asLowerCamelCase(Str::getShortClassName($owningClass))
                ),
                '',
                sprintf(
                    'NOTE: If a <comment>%s</comment> may *change* from one <comment>%s</comment> to another, answer "no".',
                    Str::getShortClassName($owningClass),
                    Str::getShortClassName($inverseClass)
                ),
            ]);

            return $io->confirm(sprintf('Do you want to automatically delete orphaned <comment>%s</comment> objects (orphanRemoval)?', $owningClass), false);
        };

        $askInverseSide = function (DocumentRelation $relation) use ($io) {
            if ($this->isClassInVendor($relation->getInverseClass())) {
                $relation->setMapInverseRelation(false);

                return;
            }

            // recommend an inverse side, except for OneToOne, where it's inefficient
            $recommendMappingInverse = DocumentRelation::ONE_TO_ONE !== $relation->getType();

            $getterMethodName = 'get'.Str::asCamelCase(Str::getShortClassName($relation->getOwningClass()));
            if (DocumentRelation::ONE_TO_ONE !== $relation->getType()) {
                // pluralize!
                $getterMethodName = Str::singularCamelCaseToPluralCamelCase($getterMethodName);
            }
            $mapInverse = $io->confirm(
                sprintf(
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
            case DocumentRelation::REFERENCE_ONE:
                $relation = new DocumentRelation(
                    DocumentRelation::REFERENCE_ONE,
                    $generatedDocumentClass,
                    $targetDocumentClass
                );
                $relation->setOwningProperty($newFieldName);

                $relation->setIsNullable($askIsNullable(
                    $relation->getOwningProperty(),
                    $relation->getOwningClass()
                ));

                $askInverseSide($relation);
                if ($relation->getMapInverseRelation()) {
                    $io->comment(sprintf(
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
            case DocumentRelation::REFERENCE_MANY:
                // we *actually* create a ManyToOne, but populate it differently
                $relation = new DocumentRelation(
                    DocumentRelation::REFERENCE_ONE,
                    $targetDocumentClass,
                    $generatedDocumentClass
                );
                $relation->setInverseProperty($newFieldName);

                $io->comment(sprintf(
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
            case DocumentRelation::MANY_TO_MANY:
                $relation = new DocumentRelation(
                    DocumentRelation::MANY_TO_MANY,
                    $generatedDocumentClass,
                    $targetDocumentClass
                );
                $relation->setOwningProperty($newFieldName);

                $askInverseSide($relation);
                if ($relation->getMapInverseRelation()) {
                    $io->comment(sprintf(
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
            case DocumentRelation::ONE_TO_ONE:
                $relation = new DocumentRelation(
                    DocumentRelation::ONE_TO_ONE,
                    $generatedDocumentClass,
                    $targetDocumentClass
                );
                $relation->setOwningProperty($newFieldName);

                $relation->setIsNullable($askIsNullable(
                    $relation->getOwningProperty(),
                    $relation->getOwningClass()
                ));

                $askInverseSide($relation);
                if ($relation->getMapInverseRelation()) {
                    $io->comment(sprintf(
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

    private function askRelationType(ConsoleStyle $io, string $documentClass, string $targetDocumentClass)
    {
        $io->writeln('What type of relationship is this?');

        $originalDocumentShort = Str::getShortClassName($documentClass);
        $targetDocumentShort = Str::getShortClassName($targetDocumentClass);
        $rows = [];
        $rows[] = [
            DocumentRelation::REFERENCE_ONE,
            sprintf("Each <comment>%s</comment> relates to (has) <info>one</info> <comment>%s</comment>.\nEach <comment>%s</comment> can relate to (can have) <info>many</info> <comment>%s</comment> objects.", $originalDocumentShort, $targetDocumentShort, $targetDocumentShort, $originalDocumentShort),
        ];
        $rows[] = ['', ''];
        $rows[] = [
            DocumentRelation::REFERENCE_MANY,
            sprintf("Each <comment>%s</comment> can relate to (can have) <info>many</info> <comment>%s</comment> objects.\nEach <comment>%s</comment> relates to (has) <info>one</info> <comment>%s</comment>.", $originalDocumentShort, $targetDocumentShort, $targetDocumentShort, $originalDocumentShort),
        ];
        $rows[] = ['', ''];
        $rows[] = [
            DocumentRelation::MANY_TO_MANY,
            sprintf("Each <comment>%s</comment> can relate to (can have) <info>many</info> <comment>%s</comment> objects.\nEach <comment>%s</comment> can also relate to (can also have) <info>many</info> <comment>%s</comment> objects.", $originalDocumentShort, $targetDocumentShort, $targetDocumentShort, $originalDocumentShort),
        ];
        $rows[] = ['', ''];
        $rows[] = [
            DocumentRelation::ONE_TO_ONE,
            sprintf("Each <comment>%s</comment> relates to (has) exactly <info>one</info> <comment>%s</comment>.\nEach <comment>%s</comment> also relates to (has) exactly <info>one</info> <comment>%s</comment>.", $originalDocumentShort, $targetDocumentShort, $targetDocumentShort, $originalDocumentShort),
        ];

        $io->table([
            'Type',
            'Description',
        ], $rows);

        $question = new Question(sprintf(
            'Relation type? [%s]',
            implode(', ', DocumentRelation::getValidRelationTypes())
        ));
        $question->setAutocompleterValues(DocumentRelation::getValidRelationTypes());
        $question->setValidator(function ($type) {
            if (!\in_array($type, DocumentRelation::getValidRelationTypes())) {
                throw new \InvalidArgumentException(sprintf('Invalid type: use one of: %s', implode(', ', DocumentRelation::getValidRelationTypes())));
            }

            return $type;
        });

        return $io->askQuestion($question);
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

    private function regenerateDocuments(string $classOrNamespace, bool $overwrite, Generator $generator): void
    {
        $regenerator = new DocumentRegenerator($this->doctrineHelper, $this->fileManager, $generator, $this->documentClassGenerator, $overwrite);
        $regenerator->regenerateDocuments($classOrNamespace);
    }

    private function getPropertyNames(string $class): array
    {
        if (!class_exists($class)) {
            return [];
        }

        $reflClass = new \ReflectionClass($class);

        return array_map(static fn (\ReflectionProperty $prop) => $prop->getName(), $reflClass->getProperties());
    }

    /** @legacy Drop when Annotations are no longer supported */
    private function doesDocumentUseAttributeMapping(string $className): bool
    {
        if (!class_exists($className)) {
            $otherClassMetadatas = $this->doctrineHelper->getMetadata(Str::getNamespace($className).'\\', true);

            // if we have no metadata, we should assume this is the first class being mapped
            if (empty($otherClassMetadatas)) {
                return false;
            }

            $className = reset($otherClassMetadatas)->getName();
        }

        return $this->doctrineHelper->doesClassUsesAttributes($className);
    }

    private function getDocumentNamespace(): string
    {
        return $this->doctrineHelper->getDocumentNamespace();
    }

    private function getTypesMap(): array
    {
        return Type::getTypesMap();
    }
}
