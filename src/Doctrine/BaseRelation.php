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
    private $propertyName;
    private $targetClassName;
    private $targetPropertyName;
    private $returnType = null;
    private $returnTypeIsNullable = true;
    private $isSelfReferencing = false;
    private $mapInverseRelation = true;
    private $avoidSetter = false;

    abstract public function isOwning(): bool;

    public function getPropertyName()
    {
        return $this->propertyName;
    }

    public function setPropertyName($propertyName)
    {
        $this->propertyName = $propertyName;

        return $this;
    }

    public function getTargetClassName()
    {
        return $this->targetClassName;
    }

    public function setTargetClassName($targetClassName)
    {
        $this->targetClassName = $targetClassName;

        return $this;
    }

    public function getTargetPropertyName()
    {
        return $this->targetPropertyName;
    }

    public function setTargetPropertyName($targetPropertyName)
    {
        $this->targetPropertyName = $targetPropertyName;

        return $this;
    }

    public function isSelfReferencing(): bool
    {
        return $this->isSelfReferencing;
    }

    public function setIsSelfReferencing(bool $isSelfReferencing)
    {
        $this->isSelfReferencing = $isSelfReferencing;

        return $this;
    }

    public function getMapInverseRelation(): bool
    {
        return $this->mapInverseRelation;
    }

    public function setMapInverseRelation(bool $mapInverseRelation)
    {
        $this->mapInverseRelation = $mapInverseRelation;

        return $this;
    }

    public function isAvoidSetter() {
        return $this->avoidSetter;
    }

    public function avoidSetter(bool $avoidSetter = true) {
        $this->avoidSetter = $avoidSetter;

        return $this;
    }

    public function getReturnType() {
        return $this->returnType;
    }

    public function setReturnType(string $returnType, $returnTypeIsNullable = true) {
        $this->returnType = $returnType;
        $this->returnTypeIsNullable = $returnTypeIsNullable;

        return $this;
    }

    public function isReturnTypeNullable() {
        return $this->returnTypeIsNullable;
    }
}
