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
 * @author Jesse Rushlow <jr@rushlow.dev>
 */
class UseStatementGenerator
{
    private $classesToBeImported;

    /**
     * @param string[]|array<string, string> $classesToBeImported
     */
    public function __construct(array $classesToBeImported)
    {
        $this->classesToBeImported = $classesToBeImported;
    }

    public function generateUseStatements(): string
    {
        $transformed = [];
        $aliases = [];

        foreach ($this->classesToBeImported as $key => $class) {
            if (\is_array($class)) {
                $aliasClass = key($class);
                $aliases[$aliasClass] = $class[$aliasClass];
                $class = $aliasClass;
            }

            $transformed[$key] = str_replace('\\', ' ', $class);
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
    public function addUseStatement($className): void
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
