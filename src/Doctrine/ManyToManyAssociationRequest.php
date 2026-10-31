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

/**
 * Signals that a ManyToMany relationship should be represented by a
 * dedicated association entity instead, because it carries additional
 * properties.
 *
 * @internal
 */
final class ManyToManyAssociationRequest
{
    public function __construct(
        private string $owningClass,
        private string $targetClass,
    ) {
    }

    public function getOwningClass(): string
    {
        return $this->owningClass;
    }

    public function getTargetClass(): string
    {
        return $this->targetClass;
    }
}
