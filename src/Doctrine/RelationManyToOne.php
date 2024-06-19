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

use Doctrine\ORM\Mapping\ManyToOneAssociationMapping;

/**
 * @internal
 */
final class RelationManyToOne extends BaseRelation
{
    public static function createFromObject(ManyToOneAssociationMapping|array $mapping): self
    {
        /* @legacy Remove conditional when ORM 2.x is no longer supported! */
        if (\is_array($mapping)) {
            return new self(
                propertyName: $mapping['fieldName'],
                targetClassName: $mapping['targetEntity'],
                targetPropertyName: $mapping['inversedBy'],
                mapInverseRelation: null !== $mapping['inversedBy'],
                isOwning: true,
                isNullable: $mapping['joinColumns'][0]['nullable'] ?? true,
            );
        }

        return new self(
            propertyName: $mapping->fieldName,
            targetClassName: $mapping->targetEntity,
            targetPropertyName: $mapping->inversedBy,
            mapInverseRelation: null !== $mapping->inversedBy,
            isOwning: true,
            isNullable: $mapping->joinColumns[0]->nullable ?? true,
        );
    }
}
