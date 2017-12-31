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

use Doctrine\Common\Persistence\ManagerRegistry;
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
            throw new RuntimeCommandException('This value cannot be blank');
        }

        return $value;
    }

    public static function validateLength($length)
    {
        if (!$length) {
            return $length;
        }

        $result = filter_var($length, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1],
        ]);

        if (false === $result) {
            throw new RuntimeCommandException(sprintf('Invalid length "%s".', $length));
        }

        return $length;
    }

    public static function validatePrecision($precision)
    {
        if (!$precision) {
            return $precision;
        }

        $result = filter_var($precision, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1, 'max_range' => 65],
        ]);

        if (false === $result) {
            throw new RuntimeCommandException(sprintf('Invalid precision "%s".', $precision));
        }

        return $precision;
    }

    public static function validateScale($scale)
    {
        if (!$scale) {
            return $scale;
        }

        $result = filter_var($scale, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 0, 'max_range' => 30],
        ]);

        if (false === $result) {
            throw new RuntimeCommandException(sprintf('Invalid scale "%s".', $scale));
        }

        return $scale;
    }

    public static function validateBoolean($value)
    {
        if ('yes' == $value) {
            return true;
        }

        if ('no' == $value) {
            return false;
        }

        if (null === $valueAsBool = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)) {
            throw new RuntimeCommandException(sprintf('Invalid bool value "%s".', $value));
        }

        return $valueAsBool;
    }

    public static function validateDoctrineFieldName(string $name, ManagerRegistry $registry)
    {
        // check reserved words
        if ($registry->getConnection()->getDatabasePlatform()->getReservedKeywordsList()->isKeyword($name)) {
            throw new \InvalidArgumentException(sprintf('Name "%s" is a reserved word.', $name));
        }
        // check for valid PHP variable name
        if (null !== $name && !Str::isValidPhpVariableName($name)) {
            throw new \InvalidArgumentException(sprintf('"%s" is not a valid PHP property name.', $name));
        }

        return $name;
    }
}
