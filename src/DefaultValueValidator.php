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
 * Validator made for default values.
 */
class DefaultValueValidator
{
    /**
     * All currently supported types with default value.
     */
    public const SUPPORTED_TYPES = [
        'string',
        'ascii_string',
        'text',
        'boolean',
        'integer',
        'smallint',
        'bigint',
        'float',
    ];

    /**
     * Gets the validator that belongs to type.
     *
     * @param string $type The type in doctrine format
     *
     * @return callable|null The validator that belongs to type
     */
    public static function getValidator(string $type): ?callable
    {
        switch ($type) {
            case 'boolean':
                return self::validateBoolean(...);
            case 'float':
                return self::validateFloat(...);
            case 'integer':
            case 'smallint':
            case 'bigint':
                return self::validateInt(...);
        }

        return null;
    }

    public static function validateBoolean(mixed $value): bool
    {
        if (\in_array($value, ['true', 'false'])) {
            return 'true' === $value;
        }
        throw new RuntimeCommandException(sprintf('Value %s is invalid for type boolean', $value));
    }

    public static function validateFloat(mixed $value): float
    {
        if (is_numeric($value)) {
            return (float) $value;
        }
        throw new RuntimeCommandException(sprintf('Value %s is invalid for type float', $value));
    }

    public static function validateInt(mixed $value): int
    {
        if (is_numeric($value)) {
            $val = (int) $value;
            if ('0' === $value && 0 === $val) {
                return $val;
            } elseif (0 !== $val) {
                return $val;
            }
        }
        throw new RuntimeCommandException(sprintf('Value %s is invalid for type int', $value));
    }
}
