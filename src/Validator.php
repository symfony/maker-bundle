<?php

/*
 * This file is part of the Symfony MakerBundle package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\MakerBundle;

use Symfony\Bundle\MakerBundle\Exception\RuntimeCommandException;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 * @author Ryan Weaver <weaverryan@gmail.com>
 *
 * @internal
 */
final class Validator
{
    public static function validateClassName(string $className, string $errorMessage = ''): string
    {
        // remove potential opening slash so we don't match on it
        $pieces = explode('\\', ltrim($className, '\\'));

        foreach ($pieces as $piece) {
            if (!preg_match('/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/', $piece)) {
                $errorMessage = $errorMessage ?: sprintf('"%s" is not valid as a PHP class name (it must start with a letter or underscore, followed by any number of letters, numbers, or underscores)', $className);

                throw new RuntimeCommandException($errorMessage);
            }
        }

        // return original class name
        return $className;
    }

    public static function notBlank(string $value = null): string
    {
        if (null === $value || '' === $value) {
            throw new RuntimeCommandException('This value should not be blank');
        }

        return $value;
    }

    public static function existsOrNull(string $className = null, array $entites = [])
    {
        if (null !== $className) {
            self::validateClassName($className);

            if (0 === strpos($className, '\\')) {
                self::classExists($className);
            } else {
                self::entityExists($className, $entites);
            }
        }

        return $className;
    }

    public static function classExists(string $className, string $errorMessage = ''): string
    {
        self::notBlank($className);

        if (!class_exists($className)) {
            $errorMessage = $errorMessage ?: sprintf('Class "%s" does\'t exists. Please enter existing full class name', $className);

            throw new RuntimeCommandException($errorMessage);
        }

        return $className;
    }

    public static function entityExists(string $className = null, array $entites = []): string
    {
        self::notBlank($className);

        if (empty($entites)) {
            throw new RuntimeCommandException(sprintf('There is no registered entites. Please create entity before use this command', $className));
        }

        if (0 === strpos($className, '\\')) {
            self::classExists($className, sprintf('Entity "%s" does\'t exists. Please enter existing one or create new', $className));
        }

        if (!in_array($className, $entites)) {
            throw new RuntimeCommandException(sprintf('Entity "%s" does\'t exists. Please enter existing one or create new', $className));
        }

        return $className;
    }
}
