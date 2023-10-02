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
     */
    public function __construct(
        private array $classesToBeImported,
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
                $statements .= sprintf("use %s;\n", $importedClass);
                continue;
            }

            $aliasClass = key($importedClass);
            $statements .= sprintf("use %s as %s;\n", $aliasClass, $aliases[$aliasClass]);
        }

        return $statements;
    }

    /**
     * @param string|string[]|array<string, string> $className
     */
    public function addUseStatement(array|string $className): void
    {
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
}
