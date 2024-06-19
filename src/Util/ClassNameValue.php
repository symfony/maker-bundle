<?php

/*
 * This file is part of the Symfony MakerBundle package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\MakerBundle\Util;

use Symfony\Bundle\MakerBundle\Str;

/**
 * @internal
 */
final class ClassNameValue implements \Stringable
{
    public function __construct(
        private string $typeHint,
        private string $fullClassName,
    ) {
    }

    public function getShortName(): string
    {
        if ($this->isSelf()) {
            return Str::getShortClassName($this->fullClassName);
        }

        return $this->typeHint;
    }

    public function isSelf(): bool
    {
        return 'self' === $this->typeHint;
    }

    public function __toString(): string
    {
        return $this->getShortName();
    }
}
