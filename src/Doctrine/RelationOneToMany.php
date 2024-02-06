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

use Doctrine\ORM\Mapping\OneToManyAssociationMapping;
use Symfony\Bundle\MakerBundle\Str;

/**
 * @internal
 */
final class RelationOneToMany extends BaseCollectionRelation
{
    public function getTargetGetterMethodName(): string
    {
        return 'get'.Str::asCamelCase($this->getTargetPropertyName());
    }

    public function getTargetSetterMethodName(): string
    {
        return 'set'.Str::asCamelCase($this->getTargetPropertyName());
    }

    public function isMapInverseRelation(): bool
    {
        throw new \Exception('OneToMany IS the inverse side!');
    }

    public static function createFromObject(OneToManyAssociationMapping|array $mapping): self
    {
        /* @legacy Remove conditional when ORM 2.x is no longer supported! */
        if (\is_array($mapping)) {
            return new self(
                propertyName: $mapping['fieldName'],
                targetClassName: $mapping['targetEntity'],
                targetPropertyName: $mapping['mappedBy'],
                orphanRemoval: $mapping['orphanRemoval'],
            );
        }

        return new self(
            propertyName: $mapping->fieldName,
            targetClassName: $mapping->targetEntity,
            targetPropertyName: $mapping->mappedBy,
            orphanRemoval: $mapping->orphanRemoval,
        );
    }
}
