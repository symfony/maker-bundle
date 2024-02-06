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

use Doctrine\ORM\Mapping\ManyToManyInverseSideMapping;
use Doctrine\ORM\Mapping\ManyToManyOwningSideMapping;
use Symfony\Bundle\MakerBundle\Str;

/**
 * @internal
 */
final class RelationManyToMany extends BaseCollectionRelation
{
    public function getTargetSetterMethodName(): string
    {
        return 'add'.Str::asCamelCase(Str::pluralCamelCaseToSingular($this->getTargetPropertyName()));
    }

    public function getTargetRemoverMethodName(): string
    {
        return 'remove'.Str::asCamelCase(Str::pluralCamelCaseToSingular($this->getTargetPropertyName()));
    }

    public static function createFromObject(ManyToManyInverseSideMapping|ManyToManyOwningSideMapping $mapping): self
    {
        if ($mapping instanceof ManyToManyOwningSideMapping) {
            return new self(
                propertyName: $mapping->fieldName,
                targetClassName: $mapping->targetEntity,
                targetPropertyName: $mapping->inversedBy, // @TODO _ @legacy Is this correct?
                mapInverseRelation: (null !== $mapping->inversedBy),
                isOwning: $mapping->isOwningSide(),
            );
        }

        return new self(
            propertyName: $mapping->fieldName,
            targetClassName: $mapping->targetEntity,
            targetPropertyName: $mapping->mappedBy, // @TODO _ @legacy mappedBy Doesnt exist on object
            mapInverseRelation: true,
            isOwning: $mapping->isOwningSide(),
        );
    }
}
