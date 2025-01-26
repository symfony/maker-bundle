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

use Symfony\Bundle\MakerBundle\Exception\RuntimeCommandException;
use Symfony\Bundle\MakerBundle\Str;

/**
 * Converts fully qualified class names into sorted use statements for templates.
 *
 * @author Jesse Rushlow <jr@rushlow.dev>
 *
 * @internal
 */
final class UseStatementGenerator implements \Stringable
{
    /**
     * For use statements that contain aliases, the $classesToBeImported array
     * may contain an array(s) like [\Some\Class::class => 'ZYX']. The generated
     * use statement would appear as "use Some\Class::class as 'ZXY'". It is ok
     * to mix non-aliases classes with aliases.
     *
     * @param string[]|array<string, string> $classesToBeImported
     * @param string[]                       $concideredShortScoped
     */
    public function __construct(
        private array $classesToBeImported,
        private readonly array $concideredShortScoped = [],
    ) {
    }

    public function __toString(): string
    {
        $transformed = [];
        $aliases = [];

        foreach ($this->classesToBeImported as $key => $class) {
            if (\is_array($class)) {
                $aliasClass = key($class);
                $aliases[$aliasClass] = $class[$aliasClass];
                $class = $aliasClass;
            }

            $transformedClass = str_replace('\\', ' ', $class);
            // Let's not add the class again if it already exists.
            if (!\in_array($transformedClass, $transformed, true)) {
                $transformed[$key] = $transformedClass;
            }
        }

        asort($transformed);

        $statements = '';

        foreach ($transformed as $key => $class) {
            $importedClass = $this->classesToBeImported[$key];

            if (!\is_array($importedClass)) {
                $statements .= \sprintf("use %s;\n", $importedClass);
                continue;
            }

            $aliasClass = key($importedClass);
            $statements .= \sprintf("use %s as %s;\n", $aliasClass, $aliases[$aliasClass]);
        }

        return $statements;
    }

    /**
     * @param string|string[]|array<string, string> $className
     */
    public function addUseStatement(array|string $className, ?string $aliasPrefixIfExist = null): void
    {
        if (null !== $aliasPrefixIfExist) {
            if (\is_array($className)) {
                throw new RuntimeCommandException('$aliasIfScoped must be null if $className is an array.');
            }

            if ($this->isShortNameScoped($className)) {
                $this->classesToBeImported[] = [$className => $aliasPrefixIfExist.Str::getShortClassName($className)];

                return;
            }
        }

        if (\is_array($className)) {
            $this->classesToBeImported = array_merge($this->classesToBeImported, $className);

            return;
        }

        // Let's not add the class again if it already exists.
        if (\in_array($className, $this->classesToBeImported, true)) {
            return;
        }

        $this->classesToBeImported[] = $className;
    }

    public function getShortName(string $className): string
    {
        foreach ($this->classesToBeImported as $class) {
            $alias = null;
            if (\is_array($class)) {
                $alias = current($class);
                $class = key($class);
            }

            if (null === $alias) {
                if ($class === $className) {
                    return Str::getShortClassName($class);
                }

                if (str_starts_with($className, $class)) {
                    return Str::getShortClassName($class).substr($className, \strlen($class));
                }

                continue;
            }

            if ($class === $className) {
                return $alias;
            }

            if (str_starts_with($className, $class)) {
                return $alias.substr($className, \strlen($class));
            }
        }

        throw new RuntimeCommandException(\sprintf('The class "%s" is not found in use statement.', $className));
    }

    public function hasUseStatement(string $className): bool
    {
        foreach ($this->classesToBeImported as $class) {
            if (\is_array($class)) {
                $class = key($class);
            }

            if ($class === $className) {
                return true;
            }
        }

        return false;
    }

    private function isShortNameScoped(string $className): bool
    {
        $shortClassName = Str::getShortClassName($className);

        if (\in_array($shortClassName, $this->concideredShortScoped)) {
            return true;
        }

        foreach ($this->classesToBeImported as $class) {
            if (\is_array($class)) {
                $tmp = $class;
                $class = key($class);
                $shortClass = current($tmp);
            } else {
                $shortClass = Str::getShortClassName($class);
            }

            // If class already exist, considered as not scoped.
            if ($class === $className) {
                return false;
            }

            if ($shortClassName === $shortClass) {
                return true;
            }
        }

        return false;
    }
}
