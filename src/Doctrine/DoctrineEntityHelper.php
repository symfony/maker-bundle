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

/**
 * @author Sadicov Vladimir <sadikoff@gmail.com>
 */
final class DoctrineEntityHelper
{
    private $metadataFactory = null;

    public function __construct(ManagerRegistry $registry = null)
    {
        if (null !== $registry) {
            $this->metadataFactory = new DoctrineMetadataFactory($registry);
        }
    }

    public function isDoctrineConnected(): bool
    {
        return null !== $this->metadataFactory;
    }

    public function getFormFieldsFromEntity(string $entityClassName): array
    {
        $metadata = $this->getEntityMetadata($entityClassName);

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

    /**
     * @param $entityClassName
     *
     * @return \Doctrine\ORM\Mapping\ClassMetadata|null
     */
    public function getEntityMetadata($entityClassName)
    {
        return $this->metadataFactory->getMetadataForClass('App\\Entity\\'.$entityClassName);
    }
}
