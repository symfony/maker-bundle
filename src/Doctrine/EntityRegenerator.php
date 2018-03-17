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

use Doctrine\ORM\Mapping\ClassMetadata;
use Symfony\Bridge\Doctrine\ManagerRegistry;
use Symfony\Bundle\MakerBundle\Exception\RuntimeCommandException;
use Symfony\Bundle\MakerBundle\FileManager;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\Str;
use Symfony\Bundle\MakerBundle\Util\ClassSourceManipulator;

/**
 * @internal
 */
final class EntityRegenerator
{
    private $doctrineRegistry;
    private $fileManager;
    private $generator;
    private $projectDirectory;
    private $overwrite;
    private $metadataFactory;

    public function __construct(ManagerRegistry $doctrineRegistry, FileManager $fileManager, Generator $generator, string $projectDirectory, bool $overwrite)
    {
        $this->doctrineRegistry = $doctrineRegistry;
        $this->fileManager = $fileManager;
        $this->generator = $generator;
        $this->projectDirectory = $projectDirectory;
        $this->overwrite = $overwrite;
        $this->metadataFactory = new DoctrineMetadataFactory($this->doctrineRegistry);
    }

    public function regenerateEntities(string $classOrNamespace)
    {
        if (class_exists($classOrNamespace)) {
            $metadata = $this->metadataFactory->getMetadataForClass($classOrNamespace);

            if (null === $metadata) {
                throw new RuntimeCommandException(sprintf('Could not find Doctrine metadata for "%s". Is it mapped as an entity?', $classOrNamespace));
            }

            $metadata = [$metadata];
        } else {
            $metadata = $this->metadataFactory->getMetadataForNamespace($classOrNamespace);

            if (empty($metadata)) {
                throw new RuntimeCommandException(sprintf('No entities were found in the "%s" namespace.', $classOrNamespace));
            }
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

            if ($classMetadata->customRepositoryClassName) {
                $this->generateRepository($classMetadata);
            }

            $manipulator = $this->createClassManipulator($classPath);
            $operations[$classPath] = $manipulator;

            foreach ($classMetadata->fieldMappings as $fieldName => $mapping) {
                $manipulator->addEntityField($fieldName, $mapping);
            }

            $getIsNullable = function (array $mapping) {
                if (!isset($mapping['joinColumns'][0]) || !isset($mapping['joinColumns'][0]['nullable'])) {
                    // the default for relationships IS nullable
                    return true;
                }

                return $mapping['joinColumns'][0]['nullable'];
            };

            foreach ($classMetadata->associationMappings as $fieldName => $mapping) {
                switch ($mapping['type']) {
                    case ClassMetadata::MANY_TO_ONE:
                        $relation = (new RelationManyToOne())
                            ->setPropertyName($mapping['fieldName'])
                            ->setIsNullable($getIsNullable($mapping))
                            ->setTargetClassName($mapping['targetEntity'])
                            ->setTargetPropertyName($mapping['inversedBy'])
                            ->setMapInverseRelation(null !== $mapping['inversedBy'])
                        ;

                        $manipulator->addManyToOneRelation($relation);

                        break;
                    case ClassMetadata::ONE_TO_MANY:
                        $relation = (new RelationOneToMany())
                            ->setPropertyName($mapping['fieldName'])
                            ->setTargetClassName($mapping['targetEntity'])
                            ->setTargetPropertyName($mapping['mappedBy'])
                            ->setOrphanRemoval($mapping['orphanRemoval'])
                        ;

                        $manipulator->addOneToManyRelation($relation);

                        break;
                    case ClassMetadata::MANY_TO_MANY:
                        $relation = (new RelationManyToMany())
                            ->setPropertyName($mapping['fieldName'])
                            ->setTargetClassName($mapping['targetEntity'])
                            ->setTargetPropertyName($mapping['mappedBy'])
                            ->setIsOwning($mapping['isOwningSide'])
                            ->setMapInverseRelation($mapping['isOwningSide'] ? (null !== $mapping['inversedBy']) : true)
                        ;

                        $manipulator->addManyToManyRelation($relation);

                        break;
                    case ClassMetadata::ONE_TO_ONE:
                        $relation = (new RelationOneToOne())
                            ->setPropertyName($mapping['fieldName'])
                            ->setTargetClassName($mapping['targetEntity'])
                            ->setTargetPropertyName($mapping['isOwningSide'] ? $mapping['inversedBy'] : $mapping['mappedBy'])
                            ->setIsOwning($mapping['isOwningSide'])
                            ->setMapInverseRelation($mapping['isOwningSide'] ? (null !== $mapping['inversedBy']) : true)
                            ->setIsNullable($getIsNullable($mapping))
                        ;

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
            $this->fileManager->getFileContents($classPath),
            $this->overwrite,
            // use annotations
            // if properties need to be generated then, by definition,
            // some non-annotation config is being used, and so, the
            // properties should not have annotations added to them
            false
        );
    }

    private function getPathOfClass(string $class): string
    {
        return (new \ReflectionClass($class))->getFileName();
    }

    private function generateRepository(ClassMetadata $metadata)
    {
        if (!$metadata->customRepositoryClassName) {
            return;
        }

        if (class_exists($metadata->customRepositoryClassName)) {
            // repository already exists
            return;
        }

        // duplication in MakeEntity
        $entityClassName = Str::getShortClassName($metadata->name);

        $this->generator->generateClass(
            $metadata->customRepositoryClassName,
            'doctrine/Repository.tpl.php',
            [
                'entity_full_class_name' => $metadata->name,
                'entity_class_name' => $entityClassName,
                'entity_alias' => strtolower($entityClassName[0]),
            ]
        );

        $this->generator->writeChanges();
    }
}
