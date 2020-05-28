<?php

/*
 * This file is part of the Symfony MakerBundle package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\MakerBundle\Util;

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\Doctrine\DoctrineHelper;
use Symfony\Bundle\MakerBundle\Doctrine\EntityClassGenerator;
use Symfony\Bundle\MakerBundle\Doctrine\EntityRegenerator;
use Symfony\Bundle\MakerBundle\Doctrine\EntityRelation;
use Symfony\Bundle\MakerBundle\FileManager;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\Str;
use Symfony\Bundle\MakerBundle\Validator;
use Symfony\Component\Console\Question\Question;

/**
 * @author Ryan Weaver <weaverryan@gmail.com>
 *
 * @internal
 */
class MakeEntityHelper
{
    private $doctrineHelper;
    private $fileManager;
    private $apiFilters = [];
    private $apiFilterStrategies = [];

    public static $availableFilters = [
        'ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter',
        'ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\DateFilter',
        'ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\BooleanFilter',
        'ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\NumericFilter',
        'ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\RangeFilter',
        'ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\ExistsFilter',
        'ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\OrderFilter',
        'ApiPlatform\Core\Bridge\Elasticsearch\DataProvider\Filter\MatchFilter',
        'ApiPlatform\Core\Bridge\Elasticsearch\DataProvider\Filter\TermFilter',
    ];

    private static $availableSearchFilterStrategies = [
        'exact',
        'partial',
        'start',
        'end',
        'word_start',
        'iexact',
        'ipartial',
        'istart',
        'iend',
        'iword_start',
    ];

    private static $availableDateFilterStrategies = [
        'EXCLUDE_NULL',
        'INCLUDE_NULL_BEFORE',
        'INCLUDE_NULL_AFTER',
        'INCLUDE_NULL_BEFORE_AND_AFTER',
    ];

    public const NUMERIC_TYPES = [
        'integer',
        'smallint',
        'bigint',
        'guid',
        'float',
    ];

    public const DATE_TYPES = [
        'datetime',
        'date',
        'time',
    ];

    public function __construct(DoctrineHelper $doctrineHelper, FileManager $fileManager, EntityClassGenerator $entityClassGenerator)
    {
        $this->doctrineHelper = $doctrineHelper;
        $this->fileManager = $fileManager;
        $this->entityClassGenerator = $entityClassGenerator;
    }

    public function generateEntityFields($io, $entityClassDetails, $entityPath, $overwrite, $apiOption = false)
    {
        $currentFields = $this->getPropertyNames($entityClassDetails->getFullName());
        $manipulator = $this->createClassManipulator($entityPath, $io, $overwrite);

        $isFirstField = true;
        while (true) {
            $newField = $this->askForNextField($io, $currentFields, $entityClassDetails->getFullName(), $isFirstField, $apiOption);
            $isFirstField = false;

            if (null === $newField) {
                break;
            }

            $fileManagerOperations = [];
            $fileManagerOperations[$entityPath] = $manipulator;

            if (\is_array($newField)) {
                $annotationOptions = $newField;
                unset($annotationOptions['fieldName']);
                if (!empty($this->apiFilters)) {
                    $this->addEntityFieldWithApiFilters($manipulator, $newField['fieldName'], $annotationOptions);
                } else {
                    $manipulator->addEntityField($newField['fieldName'], $annotationOptions);
                }

                $currentFields[] = $newField['fieldName'];
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
                if (\is_string($manipulatorOrMessage)) {
                    $io->comment($manipulatorOrMessage);
                } else {
                    $this->fileManager->dumpFile($path, $manipulatorOrMessage->getSourceCode());
                }
            }
        }

        $io->writeln(' <bg=green;fg=white> Success! </>');
        $io->text([
            'Next: When you\'re ready, create a migration with <info>php bin/console make:migration</info>',
            '',
        ]);
    }

    public function askForNextField(ConsoleStyle $io, array $fields, string $entityClass, bool $isFirstField, bool $apiOptions = false)
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
            $defaultType = 'datetime';
        } elseif ('_id' === $suffix) {
            $defaultType = 'integer';
        } elseif (0 === strpos($snakeCasedField, 'is_')) {
            $defaultType = 'boolean';
        } elseif (0 === strpos($snakeCasedField, 'has_')) {
            $defaultType = 'boolean';
        } elseif ('uuid' === $snakeCasedField) {
            $defaultType = 'uuid';
        } elseif ('guid' === $snakeCasedField) {
            $defaultType = 'guid';
        }

        $type = null;
        $types = Type::getTypesMap();
        // remove deprecated json_array
        unset($types[Type::JSON_ARRAY]);

        $allValidTypes = array_merge(
            array_keys($types),
            EntityRelation::getValidRelationTypes(),
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

        if ('relation' === $type || \in_array($type, EntityRelation::getValidRelationTypes())) {
            return $this->askRelationDetails($io, $entityClass, $type, $fieldName);
        }

        // this is a normal field
        $data = ['fieldName' => $fieldName, 'type' => $type];
        if ('string' === $type) {
            // default to 255, avoid the question
            $data['length'] = $io->ask('Field length', 255, [Validator::class, 'validateLength']);
        } elseif ('decimal' === $type) {
            // 10 is the default value given in \Doctrine\DBAL\Schema\Column::$_precision
            $data['precision'] = $io->ask('Precision (total number of digits stored: 100.00 would be 5)', 10, [Validator::class, 'validatePrecision']);

            // 0 is the default value given in \Doctrine\DBAL\Schema\Column::$_scale
            $data['scale'] = $io->ask('Scale (number of decimals to store: 100.00 would be 2)', 0, [Validator::class, 'validateScale']);
        }

        if ($io->confirm('Can this field be null in the database (nullable)', false)) {
            $data['nullable'] = true;
        }

        if (true === $apiOptions) {
            $data = $this->createApiFilter($io, $data);
        }

        return $data;
    }

    private function createApiFilter($io, $data)
    {
        $apiFilter = null;
        while (null === $apiFilter) {
            $question = new Question('Do you want to add a filter for your api resource? (enter <comment>?</comment> to see all filters)');
            $question->setAutocompleterValues($this->getFiltersMatchingCurrentType($data, true));
            $apiFilter = $this->getApiFilterFullClassNameIfExists($io->askQuestion($question));

            if (null === $apiFilter) {
                return $data;
            }

            if ('?' === $apiFilter) {
                foreach ($this->getFiltersMatchingCurrentType($data) as $filter) {
                    $io->writeln(sprintf('  * <comment>%s</comment>', $filter));
                }

                $apiFilter = null;
                continue;
            }

            if (!\in_array($apiFilter, self::$availableFilters)) {
                $io->error(sprintf('Invalid filter "%s".', $apiFilter));
                $io->writeln('');

                $apiFilter = null;
                continue;
            }

            if (!$this->isTypeCompatibleWithApiFilter($data, $apiFilter)) {
                $io->error(sprintf('The type "%s" is not compatible with the filter "%s"', $data['type'], $apiFilter));

                $apiFilter = null;
                continue;
            }

            $this->apiFilters[] = $apiFilter;

            $classnameFilter = Str::getShortClassName($apiFilter);

            if ('TermFilter' === $classnameFilter || 'MatchFilter' === $classnameFilter) {
                $io->note('Elasticsearch is required for this Filter');
                $io->writeln(' see: <href=https://api-platform.com/docs/core/elasticsearch/>Elasticsearch Support ApiPlatform</>');
                $io->writeln('');
            }

            if ('DateFilter' === $classnameFilter || 'SearchFilter' === $classnameFilter) {
                $strategyChoice = null;
                while (null === $strategyChoice) {
                    $question = new Question('Do you want to add a strategy for your filter? (enter <comment>?</comment> to see all strategies)');

                    $availableStrategies = 'DateFilter' === $classnameFilter
                        ? self::$availableDateFilterStrategies
                        : self::$availableSearchFilterStrategies;

                    $question->setAutocompleterValues($availableStrategies);
                    $strategy = $io->askQuestion($question);

                    if (null === $strategy) {
                        break;
                    }

                    if ('?' === $strategy) {
                        foreach ($availableStrategies as $strategy) {
                            $io->writeln(sprintf('  * <comment>%s</comment>', $strategy));
                        }

                        $strategyChoice = null;
                        continue;
                    }

                    if (!\in_array($strategy, $availableStrategies)) {
                        $io->error(sprintf('Invalid strategy "%s".', $strategy));
                        $io->writeln('');

                        $strategyChoice = null;
                        continue;
                    }

                    $this->apiFilterStrategies[$data['fieldName'].$classnameFilter] = 'DateFilter' === $classnameFilter ? 'DateFilter::'.$strategy : '"'.$strategy.'"';
                    $strategyChoice = true;
                }
            }

            $apiFilter = null;
        }

        return $data;
    }

    public function askRelationDetails(ConsoleStyle $io, string $generatedEntityClass, string $type, string $newFieldName)
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
                $io->error(sprintf('Unknown class "%s"', $answeredEntityClass));
                continue;
            }
        }

        // help the user select the type
        if ('relation' === $type) {
            $type = $this->askRelationType($io, $generatedEntityClass, $targetEntityClass);
        }

        $askFieldName = function (string $targetClass, string $defaultValue) use ($io) {
            return $io->ask(
                sprintf('New field name inside %s', Str::getShortClassName($targetClass)),
                $defaultValue,
                function ($name) use ($targetClass) {
                    // it's still *possible* to create duplicate properties - by
                    // trying to generate the same property 2 times during the
                    // same make:entity run. property_exists() only knows about
                    // properties that *originally* existed on this class.
                    if (property_exists($targetClass, $name)) {
                        throw new \InvalidArgumentException(sprintf('The "%s" class already has a "%s" property.', $targetClass, $name));
                    }

                    return Validator::validateDoctrineFieldName($name, $this->doctrineHelper->getRegistry());
                }
            );
        };

        $askIsNullable = function (string $propertyName, string $targetClass) use ($io) {
            return $io->confirm(sprintf(
                'Is the <comment>%s</comment>.<comment>%s</comment> property allowed to be null (nullable)?',
                Str::getShortClassName($targetClass),
                $propertyName
            ));
        };

        $askOrphanRemoval = function (string $owningClass, string $inverseClass) use ($io) {
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
            case EntityRelation::ONE_TO_MANY:
                // we *actually* create a ManyToOne, but populate it differently
                $relation = new EntityRelation(
                    EntityRelation::MANY_TO_ONE,
                    $targetEntityClass,
                    $generatedEntityClass
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
            case EntityRelation::MANY_TO_MANY:
                $relation = new EntityRelation(
                    EntityRelation::MANY_TO_MANY,
                    $generatedEntityClass,
                    $targetEntityClass
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

    public function askRelationType(ConsoleStyle $io, string $entityClass, string $targetEntityClass)
    {
        $io->writeln('What type of relationship is this?');

        $originalEntityShort = Str::getShortClassName($entityClass);
        $targetEntityShort = Str::getShortClassName($targetEntityClass);
        $rows = [];
        $rows[] = [
            EntityRelation::MANY_TO_ONE,
            sprintf("Each <comment>%s</comment> relates to (has) <info>one</info> <comment>%s</comment>.\nEach <comment>%s</comment> can relate to (can have) <info>many</info> <comment>%s</comment> objects", $originalEntityShort, $targetEntityShort, $targetEntityShort, $originalEntityShort),
        ];
        $rows[] = ['', ''];
        $rows[] = [
            EntityRelation::ONE_TO_MANY,
            sprintf("Each <comment>%s</comment> can relate to (can have) <info>many</info> <comment>%s</comment> objects.\nEach <comment>%s</comment> relates to (has) <info>one</info> <comment>%s</comment>", $originalEntityShort, $targetEntityShort, $targetEntityShort, $originalEntityShort),
        ];
        $rows[] = ['', ''];
        $rows[] = [
            EntityRelation::MANY_TO_MANY,
            sprintf("Each <comment>%s</comment> can relate to (can have) <info>many</info> <comment>%s</comment> objects.\nEach <comment>%s</comment> can also relate to (can also have) <info>many</info> <comment>%s</comment> objects", $originalEntityShort, $targetEntityShort, $targetEntityShort, $originalEntityShort),
        ];
        $rows[] = ['', ''];
        $rows[] = [
            EntityRelation::ONE_TO_ONE,
            sprintf("Each <comment>%s</comment> relates to (has) exactly <info>one</info> <comment>%s</comment>.\nEach <comment>%s</comment> also relates to (has) exactly <info>one</info> <comment>%s</comment>.", $originalEntityShort, $targetEntityShort, $targetEntityShort, $originalEntityShort),
        ];

        $io->table([
            'Type',
            'Description',
        ], $rows);

        $question = new Question(sprintf(
            'Relation type? [%s]',
            implode(', ', EntityRelation::getValidRelationTypes())
        ));
        $question->setAutocompleterValues(EntityRelation::getValidRelationTypes());
        $question->setValidator(function ($type) {
            if (!\in_array($type, EntityRelation::getValidRelationTypes())) {
                throw new \InvalidArgumentException(sprintf('Invalid type: use one of: %s', implode(', ', EntityRelation::getValidRelationTypes())));
            }

            return $type;
        });

        return $io->askQuestion($question);
    }

    public function printAvailableTypes(ConsoleStyle $io)
    {
        $allTypes = Type::getTypesMap();

        if ('Hyper' === getenv('TERM_PROGRAM')) {
            $wizard = 'wizard 🧙';
        } else {
            $wizard = '\\' === \DIRECTORY_SEPARATOR ? 'wizard' : 'wizard 🧙';
        }

        $typesTable = [
            'main' => [
                'string' => [],
                'text' => [],
                'boolean' => [],
                'integer' => ['smallint', 'bigint'],
                'float' => [],
            ],
            'relation' => [
                'relation' => 'a '.$wizard.' will help you build the relation',
                EntityRelation::MANY_TO_ONE => [],
                EntityRelation::ONE_TO_MANY => [],
                EntityRelation::MANY_TO_MANY => [],
                EntityRelation::ONE_TO_ONE => [],
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
        ];

        $printSection = function (array $sectionTypes) use ($io, &$allTypes) {
            foreach ($sectionTypes as $mainType => $subTypes) {
                unset($allTypes[$mainType]);
                $line = sprintf('  * <comment>%s</comment>', $mainType);

                if (\is_string($subTypes) && $subTypes) {
                    $line .= sprintf(' (%s)', $subTypes);
                } elseif (\is_array($subTypes) && !empty($subTypes)) {
                    $line .= sprintf(' (or %s)', implode(', ', array_map(function ($subType) {
                        return sprintf('<comment>%s</comment>', $subType);
                    }, $subTypes)));

                    foreach ($subTypes as $subType) {
                        unset($allTypes[$subType]);
                    }
                }

                $io->writeln($line);
            }

            $io->writeln('');
        };

        $io->writeln('<info>Main types</info>');
        $printSection($typesTable['main']);

        $io->writeln('<info>Relationships / Associations</info>');
        $printSection($typesTable['relation']);

        $io->writeln('<info>Array/Object Types</info>');
        $printSection($typesTable['array_object']);

        $io->writeln('<info>Date/Time Types</info>');
        $printSection($typesTable['date_time']);

        $io->writeln('<info>Other Types</info>');
        // empty the values
        $allTypes = array_map(function () {
            return [];
        }, $allTypes);
        $printSection($allTypes);
    }

    public function getPathOfClass(string $class): string
    {
        $classDetails = new ClassDetails($class);

        return $classDetails->getPath();
    }

    public function isClassInVendor(string $class): bool
    {
        $path = $this->getPathOfClass($class);

        return $this->fileManager->isPathInVendor($path);
    }

    public function regenerateEntities(string $classOrNamespace, bool $overwrite, Generator $generator)
    {
        $regenerator = new EntityRegenerator($this->doctrineHelper, $this->fileManager, $generator, $this->entityClassGenerator, $overwrite);
        $regenerator->regenerateEntities($classOrNamespace);
    }

    public function getPropertyNames(string $class): array
    {
        if (!class_exists($class)) {
            return [];
        }

        $reflClass = new \ReflectionClass($class);

        return array_map(function (\ReflectionProperty $prop) {
            return $prop->getName();
        }, $reflClass->getProperties());
    }

    public function doesEntityUseAnnotationMapping(string $className): bool
    {
        if (!class_exists($className)) {
            $otherClassMetadatas = $this->doctrineHelper->getMetadata(Str::getNamespace($className).'\\', true);

            // if we have no metadata, we should assume this is the first class being mapped
            if (empty($otherClassMetadatas)) {
                return false;
            }

            $className = reset($otherClassMetadatas)->getName();
        }

        $driver = $this->doctrineHelper->getMappingDriverForClass($className);

        return $driver instanceof AnnotationDriver;
    }

    public function getEntityNamespace(): string
    {
        return $this->doctrineHelper->getEntityNamespace();
    }

    public function addEntityFieldWithApiFilters(ClassSourceManipulator $manipulator, string $fieldName, array $annotationOptions)
    {
        $manipulator->addUseStatementIfNecessary('ApiPlatform\\Core\\Annotation\\ApiFilter');

        $filtersAnnotations = [];
        foreach ($this->apiFilters as $filter) {
            $classnameFilter = $manipulator->addUseStatementIfNecessary($filter);

            $filterAnnotation = '@ApiFilter('.$classnameFilter.'::class';

            if (isset($this->apiFilterStrategies[$fieldName.$classnameFilter])) {
                $filterAnnotation .= ', strategy='.$this->apiFilterStrategies[$fieldName.$classnameFilter];
            }
            $filtersAnnotations[] = $filterAnnotation.')';
        }
        $this->apiFilters = [];

        $manipulator->addEntityField($fieldName, $annotationOptions, $filtersAnnotations);
    }

    public function createEntityClassQuestion(string $questionText): Question
    {
        $question = new Question($questionText);
        $question->setValidator([Validator::class, 'notBlank']);
        $question->setAutocompleterValues($this->doctrineHelper->getEntitiesForAutocomplete());

        return $question;
    }

    public function createClassManipulator(string $path, ConsoleStyle $io, bool $overwrite): ClassSourceManipulator
    {
        $manipulator = new ClassSourceManipulator($this->fileManager->getFileContents($path), $overwrite);
        $manipulator->setIo($io);

        return $manipulator;
    }

    /**
     * For autocompletion, we prefer shortClassName but we need fullClassName for
     * allowing users customizing their own filters.
     */
    public function getApiFilterFullClassNameIfExists($filter): ?string
    {
        if (null === $filter) {
            return null;
        }

        foreach (self::$availableFilters as $fullClassNameFilter) {
            if (strstr($fullClassNameFilter, $filter)) {
                return $fullClassNameFilter;
            }
        }

        return $filter;
    }

    /**
     * @param bool $asShortClassName is for autocompletion case
     */
    public function getFiltersMatchingCurrentType(array $data, bool $asShortClassName = false): array
    {
        $filteredFilters = self::$availableFilters;
        foreach ($filteredFilters as $key => $filter) {
            if (!$this->isTypeCompatibleWithApiFilter($data, $filter) || \in_array($filter, $this->apiFilters)) {
                unset($filteredFilters[$key]);
            } else {
                $filteredFilters[$key] = $asShortClassName ? Str::getShortClassName($filter) : $filter;
            }
        }

        return $filteredFilters;
    }

    public function isTypeCompatibleWithApiFilter(array $data, string $apiFilter): bool
    {
        $type = $data['type'];
        switch (Str::getShortClassName($apiFilter)) {
            case 'SearchFilter':
                return \in_array($type, self::NUMERIC_TYPES) || 'string' === $type || 'text' === $type;
            case 'DateFilter':
                return \in_array($type, self::DATE_TYPES);
            case 'BooleanFilter':
                return 'boolean' === $type;
            case 'NumericFilter':
                return \in_array($type, self::NUMERIC_TYPES);
            case 'RangeFilter':
                return \in_array($type, self::NUMERIC_TYPES);
            case 'ExistsFilter':
                return isset($data['nullable']);
            case 'OrderFilter':
                return \in_array($type, self::NUMERIC_TYPES) || 'string' === $type || 'text' === $type;
            case 'MatchFilter':
                return 'string' === $type || 'text' === $type;
            case 'TermFilter':
                return \in_array($type, self::NUMERIC_TYPES) || 'string' === $type || 'text' === $type;
            default:
                return false;
        }
    }
}
