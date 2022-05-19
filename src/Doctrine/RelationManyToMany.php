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
}
