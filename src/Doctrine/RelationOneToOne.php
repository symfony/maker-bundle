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

use Doctrine\ORM\Mapping\OneToOneInverseSideMapping;
use Doctrine\ORM\Mapping\OneToOneOwningSideMapping;
use Symfony\Bundle\MakerBundle\Str;

/**
 * @internal
 */
final class RelationOneToOne extends BaseRelation
{
    public function getTargetGetterMethodName(): string
    {
        return 'get'.Str::asCamelCase($this->getTargetPropertyName());
    }

    public function getTargetSetterMethodName(): string
    {
        return 'set'.Str::asCamelCase($this->getTargetPropertyName());
    }

    public static function createFromObject(OneToOneInverseSideMapping|OneToOneOwningSideMapping|array $mapping): self
    {
        /* @legacy Remove conditional when ORM 2.x is no longer supported! */
        if (\is_array($mapping)) {
            return new self(
                propertyName: $mapping['fieldName'],
                targetClassName: $mapping['targetEntity'],
                targetPropertyName: $mapping['isOwningSide'] ? $mapping['inversedBy'] : $mapping['mappedBy'],
                mapInverseRelation: !$mapping['isOwningSide'] || null !== $mapping['inversedBy'],
                isOwning: $mapping['isOwningSide'],
                isNullable: $mapping['joinColumns'][0]['nullable'] ?? true,
            );
        }

        if ($mapping instanceof OneToOneOwningSideMapping) {
            return new self(
                propertyName: $mapping->fieldName,
                targetClassName: $mapping->targetEntity,
                targetPropertyName: $mapping->inversedBy,
                mapInverseRelation: (null !== $mapping->inversedBy),
                isOwning: true,
                isNullable: $mapping->joinColumns[0]->nullable ?? true,
            );
        }

        return new self(
            propertyName: $mapping->fieldName,
            targetClassName: $mapping->targetEntity,
            targetPropertyName: $mapping->mappedBy,
            mapInverseRelation: true,
            isOwning: false,
            isNullable: $mapping->joinColumns[0]->nullable ?? true,
        );
    }
}
