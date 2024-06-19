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
 * @internal
 */
abstract class BaseRelation
{
    public function __construct(
        private string $propertyName,
        private string $targetClassName,
        private ?string $targetPropertyName = null,
        private bool $isSelfReferencing = false,
        private bool $mapInverseRelation = true,
        private bool $avoidSetter = false,
        private bool $isCustomReturnTypeNullable = false,
        private ?string $customReturnType = null,
        private bool $isOwning = false,
        private bool $orphanRemoval = false,
        private bool $isNullable = false,
    ) {
    }

    public function getPropertyName(): string
    {
        return $this->propertyName;
    }

    public function getTargetClassName(): string
    {
        return $this->targetClassName;
    }

    public function getTargetPropertyName(): ?string
    {
        return $this->targetPropertyName;
    }

    public function isSelfReferencing(): bool
    {
        return $this->isSelfReferencing;
    }

    public function getMapInverseRelation(): bool
    {
        return $this->mapInverseRelation;
    }

    public function shouldAvoidSetter(): bool
    {
        return $this->avoidSetter;
    }

    public function getCustomReturnType(): ?string
    {
        return $this->customReturnType;
    }

    public function isCustomReturnTypeNullable(): bool
    {
        return $this->isCustomReturnTypeNullable;
    }

    public function isOwning(): bool
    {
        return $this->isOwning;
    }

    public function getOrphanRemoval(): bool
    {
        return $this->orphanRemoval;
    }

    public function isNullable(): bool
    {
        return $this->isNullable;
    }
}
