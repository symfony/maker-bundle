<?php

/*
 * This file is part of the Symfony MakerBundle package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\MakerBundle\Doctrine;

use Doctrine\Common\Persistence\Mapping\MappingException as LegacyCommonMappingException;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\Persistence\Mapping\MappingException as PersistenceMappingException;
use Symfony\Bundle\MakerBundle\Exception\RuntimeCommandException;
use Symfony\Bundle\MakerBundle\FileManager;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\Util\ClassSourceManipulator;

/**
 * @internal
 */
final class EntityRegenerator
{
    public function __construct(
        private DoctrineHelper $doctrineHelper,
        private FileManager $fileManager,
        private Generator $generator,
        private EntityClassGenerator $entityClassGenerator,
        private bool $overwrite,
    ) {
    }

    public function regenerateEntities(string $classOrNamespace): void
    {
        try {
            $metadata = $this->doctrineHelper->getMetadata($classOrNamespace);
        } catch (MappingException|LegacyCommonMappingException|PersistenceMappingException) {
            $metadata = $this->doctrineHelper->getMetadata($classOrNamespace, true);
        }

        if ($metadata instanceof ClassMetadata) {
            $metadata = [$metadata];
        } elseif (class_exists($classOrNamespace)) {
            throw new RuntimeCommandException(sprintf('Could not find Doctrine metadata for "%s". Is it mapped as an entity?', $classOrNamespace));
        } elseif (empty($metadata)) {
            throw new RuntimeCommandException(sprintf('No entities were found in the "%s" namespace.', $classOrNamespace));
        }

        /** @var ClassSourceManipulator[] $operations */
        $operations = [];
        foreach ($metadata as $classMetadata) {
            if (!class_exists($classMetadata->name)) {
                // the class needs to be generated for the first time!
                $classPath = $this->generateClass($classMetadata);
            } else {
                $classPath = $this->getPathOfClass($classMetadata->name);
            }

            $mappedFields = $this->getMappedFieldsInEntity($classMetadata);

            if ($classMetadata->customRepositoryClassName) {
                $this->generateRepository($classMetadata);
            }

            $manipulator = $this->createClassManipulator($classPath);
            $operations[$classPath] = $manipulator;

            $embeddedClasses = [];

            foreach ($classMetadata->embeddedClasses as $fieldName => $mapping) {
                if (str_contains($fieldName, '.')) {
                    continue;
                }

                $className = $mapping['class'];

                $embeddedClasses[$fieldName] = $this->getPathOfClass($className);

                $operations[$embeddedClasses[$fieldName]] = $this->createClassManipulator($embeddedClasses[$fieldName]);

                if (!\in_array($fieldName, $mappedFields)) {
                    continue;
                }

                $manipulator->addEmbeddedEntity($fieldName, $className);
            }

            foreach ($classMetadata->fieldMappings as $fieldName => $mapping) {
                // skip embedded fields
                if (str_contains($fieldName, '.')) {
                    [$fieldName, $embeddedFiledName] = explode('.', $fieldName);

                    $operations[$embeddedClasses[$fieldName]]->addEntityField($embeddedFiledName, $mapping);

                    continue;
                }

                if (!\in_array($fieldName, $mappedFields)) {
                    continue;
                }

                $manipulator->addEntityField($fieldName, $mapping);
            }

            $getIsNullable = function (array $mapping) {
                if (!isset($mapping['joinColumns'][0]['nullable'])) {
                    // the default for relationships IS nullable
                    return true;
                }

                return $mapping['joinColumns'][0]['nullable'];
            };

            foreach ($classMetadata->associationMappings as $fieldName => $mapping) {
                if (!\in_array($fieldName, $mappedFields)) {
                    continue;
                }

                switch ($mapping['type']) {
                    case ClassMetadata::MANY_TO_ONE:
                        $relation = (new RelationManyToOne(
                            propertyName: $mapping['fieldName'],
                            targetClassName: $mapping['targetEntity'],
                            targetPropertyName: $mapping['inversedBy'],
                            mapInverseRelation: null !== $mapping['inversedBy'],
                            isOwning: true,
                            isNullable: $getIsNullable($mapping),
                        ));

                        $manipulator->addManyToOneRelation($relation);

                        break;
                    case ClassMetadata::ONE_TO_MANY:
                        $relation = (new RelationOneToMany(
                            propertyName: $mapping['fieldName'],
                            targetClassName: $mapping['targetEntity'],
                            targetPropertyName: $mapping['mappedBy'],
                            orphanRemoval: $mapping['orphanRemoval'],
                        ));

                        $manipulator->addOneToManyRelation($relation);

                        break;
                    case ClassMetadata::MANY_TO_MANY:
                        $relation = (new RelationManyToMany(
                            propertyName: $mapping['fieldName'],
                            targetClassName: $mapping['targetEntity'],
                            targetPropertyName: $mapping['mappedBy'],
                            mapInverseRelation: $mapping['isOwningSide'] ? (null !== $mapping['inversedBy']) : true,
                            isOwning: $mapping['isOwningSide'],
                        ));

                        $manipulator->addManyToManyRelation($relation);

                        break;
                    case ClassMetadata::ONE_TO_ONE:
                        $relation = (new RelationOneToOne(
                            propertyName: $mapping['fieldName'],
                            targetClassName: $mapping['targetEntity'],
                            targetPropertyName: $mapping['isOwningSide'] ? $mapping['inversedBy'] : $mapping['mappedBy'],
                            mapInverseRelation: $mapping['isOwningSide'] ? (null !== $mapping['inversedBy']) : true,
                            isOwning: $mapping['isOwningSide'],
                            isNullable: $getIsNullable($mapping),
                        ));

                        $manipulator->addOneToOneRelation($relation);

                        break;
                    default:
                        throw new \Exception('Unknown association type.');
                }
            }
        }

        foreach ($operations as $filename => $manipulator) {
            $this->fileManager->dumpFile(
                $filename,
                $manipulator->getSourceCode()
            );
        }
    }

    private function generateClass(ClassMetadata $metadata): string
    {
        $path = $this->generator->generateClass(
            $metadata->name,
            'Class.tpl.php',
            []
        );
        $this->generator->writeChanges();

        return $path;
    }

    private function createClassManipulator(string $classPath): ClassSourceManipulator
    {
        return new ClassSourceManipulator(
            sourceCode: $this->fileManager->getFileContents($classPath),
            overwrite: $this->overwrite,
            // if properties need to be generated then, by definition,
            // some non-annotation config is being used (e.g. XML), and so, the
            // properties should not have annotations added to them
            useAttributesForDoctrineMapping: false
        );
    }

    private function getPathOfClass(string $class): string
    {
        return (new \ReflectionClass($class))->getFileName();
    }

    private function generateRepository(ClassMetadata $metadata): void
    {
        if (!$metadata->customRepositoryClassName) {
            return;
        }

        if (class_exists($metadata->customRepositoryClassName)) {
            // repository already exists
            return;
        }

        $this->entityClassGenerator->generateRepositoryClass(
            $metadata->customRepositoryClassName,
            $metadata->name,
            false
        );

        $this->generator->writeChanges();
    }

    private function getMappedFieldsInEntity(ClassMetadata $classMetadata): array
    {
        /** @var \ReflectionClass $classReflection */
        $classReflection = $classMetadata->reflClass;

        $targetFields = [
            ...array_keys($classMetadata->fieldMappings),
            ...array_keys($classMetadata->associationMappings),
            ...array_keys($classMetadata->embeddedClasses),
        ];

        if ($classReflection) {
            // exclude traits
            $traitProperties = [];

            foreach ($classReflection->getTraits() as $trait) {
                foreach ($trait->getProperties() as $property) {
                    $traitProperties[] = $property->getName();
                }
            }

            $targetFields = array_diff($targetFields, $traitProperties);

            // exclude inherited properties
            $targetFields = array_filter($targetFields, static fn ($field) => $classReflection->hasProperty($field)
                && $classReflection->getProperty($field)->getDeclaringClass()->getName() === $classReflection->getName());
        }

        return $targetFields;
    }
}
