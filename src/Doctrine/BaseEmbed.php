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
abstract class BaseEmbed
{
    public function __construct(
        private string $propertyName,
        private string $targetClassName,
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

    public function isNullable(): bool
    {
        return $this->isNullable;
    }
}
