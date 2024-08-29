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

use Symfony\Bundle\MakerBundle\Util\ClassSource\Model\ClassData;

/**
 * @author Jesse Rushlow <jr@rushlow.dev>
 *
 * @internal
 */
final class TemplateComponentGenerator
{
    public function __construct(
        private bool $generateFinalClasses,
        private bool $generateFinalEntities,
        private string $rootNamespace,
    ) {
    }

    /**
     * @param string|null $routePath passing an empty string/null will create a route attribute without the "path" argument
     */
    public function generateRouteForControllerMethod(?string $routePath, string $routeName, array $methods = [], bool $indent = true, bool $trailingNewLine = true): string
    {
        if (!empty($routePath)) {
            $path = \sprintf('\'%s\', ', $routePath);
        }

        $attribute = \sprintf('%s#[Route(%sname: \'%s\'', $indent ? '    ' : null, $path ?? null, $routeName);

        if (!empty($methods)) {
            $attribute .= ', methods: [';

            foreach ($methods as $method) {
                $attribute .= \sprintf('\'%s\', ', $method);
            }

            $attribute = rtrim($attribute, ', ');

            $attribute .= ']';
        }

        $attribute .= \sprintf(')]%s', $trailingNewLine ? "\n" : null);

        return $attribute;
    }

    public function getPropertyType(ClassNameDetails $classNameDetails): ?string
    {
        return \sprintf('%s ', $classNameDetails->getShortName());
    }

    public function configureClass(ClassData $classMetadata): ClassData
    {
        $classMetadata->setRootNamespace($this->rootNamespace);

        if ($classMetadata->isEntity) {
            return $classMetadata->setIsFinal($this->generateFinalEntities);
        }

        return $classMetadata->setIsFinal($this->generateFinalClasses);
    }
}
