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

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Symfony\Bundle\MakerBundle\Util\ClassNameDetails;

/**
 * @author Sadicov Vladimir <sadikoff@gmail.com>
 *
 * @internal
 */
final class DoctrineEntityHelper
{
    private $metadataFactory;

    public function __construct(ManagerRegistry $registry = null)
    {
        $this->metadataFactory = null !== $registry ? new DoctrineMetadataFactory($registry) : null;
    }

    private function isDoctrineInstalled(): bool
    {
        return null !== $this->metadataFactory;
    }

    public function getEntitiesForAutocomplete(): array
    {
        $entities = [];

        if ($this->isDoctrineInstalled()) {
            $allMetadata = $this->metadataFactory->getAllMetadata();
            /** @var ClassMetadataInfo $metadata */
            foreach ($allMetadata as $metadata) {
                $entityClassDetails = new ClassNameDetails($metadata->name, 'App\\Entity');
                $entities[] = $entityClassDetails->getRelativeName();
            }
        }

        return $entities;
    }

    /**
     * @param string $entityClassName
     *
     * @return null|DoctrineEntityDetails
     *
     * @throws \Exception
     */
    public function createDoctrineDetails(string $entityClassName)
    {
        $metadata = $this->getEntityMetadata($entityClassName);

        if (null !== $metadata) {
            return new DoctrineEntityDetails(
                $metadata->customRepositoryClassName,
                $metadata->identifier[0],
                $metadata->fieldMappings,
                $this->getFormFieldsFromEntity($metadata)
            );
        }

        return null;
    }

    public function getFormFieldsFromEntity(ClassMetadataInfo $metadata): array
    {
        $fields = (array) $metadata->fieldNames;
        // Remove the primary key field if it's not managed manually
        if (!$metadata->isIdentifierNatural()) {
            $fields = array_diff($fields, $metadata->identifier);
        }
        foreach ($metadata->associationMappings as $fieldName => $relation) {
            if (ClassMetadataInfo::ONE_TO_MANY !== $relation['type']) {
                $fields[] = $fieldName;
            }
        }

        return $fields;
    }

    public function getEntityMetadata($entityClassName)
    {
        if (null === $this->metadataFactory) {
            throw new \Exception('Somehow the doctrine service is missing. Is DoctrineBundle installed?');
        }

        return $this->metadataFactory->getMetadataForClass($entityClassName);
    }
}
