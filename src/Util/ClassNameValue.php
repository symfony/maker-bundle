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
final class ClassNameValue
{
    private $typeHint;
    private $fullClassName;

    public function __construct(string $typeHint, string $fullClassName)
    {
        $this->typeHint = $typeHint;
        $this->fullClassName = $fullClassName;
    }

    public function getShortName(): string
    {
        if ('self' === $this->typeHint) {
            return Str::getShortClassName($this->fullClassName);
        }

        return $this->typeHint;
    }

    public function __toString()
    {
        return $this->getShortName();
    }
}
