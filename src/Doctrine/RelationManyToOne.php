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
    public static function createFromObject(ManyToOneAssociationMapping $data): self
    {
        return new self(
            propertyName: $data->fieldName,
            targetClassName: $data->targetEntity,
            targetPropertyName: $data->inversedBy,
            mapInverseRelation: null !== $data->inversedBy,
            isOwning: true,
            //            isNullable:
        );
    }
}
