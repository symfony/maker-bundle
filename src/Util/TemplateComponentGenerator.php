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

use ReflectionClass;
use ReflectionException;

/**
 * @author Jesse Rushlow <jr@rushlow.dev>
 *
 * @internal
 */
final class TemplateComponentGenerator
{
    private $phpCompatUtil;

    public function __construct(PhpCompatUtil $phpCompatUtil)
    {
        $this->phpCompatUtil = $phpCompatUtil;
    }

    public function generateRouteForControllerMethod(string $routePath, string $routeName, array $methods = [], bool $indent = true, bool $trailingNewLine = true): string
    {
        $attribute = sprintf('%s#[Route(\'%s\', name: \'%s\'', $indent ? '    ' : null, $routePath, $routeName);

        if (!empty($methods)) {
            $attribute .= ', methods: [';

            foreach ($methods as $method) {
                $attribute .= sprintf('\'%s\', ', $method);
            }

            $attribute = rtrim($attribute, ', ');

            $attribute .= ']';
        }

        $attribute .= sprintf(')]%s', $trailingNewLine ? "\n" : null);

        return $attribute;
    }

    public function getPropertyType(ClassNameDetails $classNameDetails): ?string
    {
        if (!$this->phpCompatUtil->canUseTypedProperties()) {
            return null;
        }

        return sprintf('%s ', $classNameDetails->getShortName());
    }

    /**
     * @throws ReflectionException
     */
    public function repositoryHasAddRemoveMethods(string $repositoryFullClassName): bool
    {
        $reflectedComponents = new ReflectionClass($repositoryFullClassName);

        return $reflectedComponents->hasMethod('add') && $reflectedComponents->hasMethod('remove');
    }
}
