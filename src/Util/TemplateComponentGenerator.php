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
 *
 * @internal
 */
final class TemplateComponentGenerator
{
    public static function generateUseStatements(array $classesToBeImported): string
    {
        $transformed = [];

        foreach ($classesToBeImported as $key => $class) {
            $transformed[$key] = str_replace('\\', ' ', $class);
        }

        asort($transformed);

        $statements = '';

        foreach ($transformed as $key => $class) {
            $statements .= sprintf("use %s;\n", $classesToBeImported[$key]);
        }

        return $statements;
    }
}
