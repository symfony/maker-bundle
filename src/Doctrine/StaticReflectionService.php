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

use Doctrine\Persistence\Mapping\ReflectionService;

/**
 * @internal replacing removed Doctrine\Persistence\Mapping\StaticReflectionService
 */
final class StaticReflectionService implements ReflectionService
{
    public function getParentClasses($class): array
    {
        return [];
    }

    public function getClassShortName($class): string
    {
        $nsSeparatorLastPosition = strrpos($class, '\\');

        if (false !== $nsSeparatorLastPosition) {
            $class = substr($class, $nsSeparatorLastPosition + 1);
        }

        return $class;
    }

    public function getClassNamespace($class): string
    {
        $namespace = '';

        if (str_contains($class, '\\')) {
            $namespace = strrev(substr(strrev($class), (int) strpos(strrev($class), '\\') + 1));
        }

        return $namespace;
    }

    public function getClass($class): \ReflectionClass
    {
        return new \ReflectionClass($class);
    }

    public function getAccessibleProperty($class, $property): ?\ReflectionProperty
    {
        return null;
    }

    public function hasPublicMethod($class, $method): bool
    {
        return true;
    }
}
