<?php

namespace Symfony\Bundle\MakerBundle;

use Symfony\Bundle\MakerBundle\Exception\RuntimeCommandException;

/**
 * Validator made for default values
 */
class DefaultValueValidator
{

    /**
     * All currently supported types with default value
     */
    public const SUPPORTED_TYPES = [
        'string',
        'ascii_string',
        'text',
        'boolean',
        'integer',
        'smallint',
        'bigint',
        'float'
    ];

    /**
     * Gets the validator that belongs to type
     *
     * @param string $type The type in doctrine format
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
        if (in_array($value, ['true', 'false'])) {
            return $value === 'true';
        }
        throw new RuntimeCommandException(sprintf("Value %s is invalid for type boolean", $value));
    }

    public static function validateFloat(mixed $value): float
    {
        if (is_numeric($value)) {
            return floatval($value);
        }
        throw new RuntimeCommandException(sprintf("Value %s is invalid for type float", $value));
    }

    public static function validateInt(mixed $value): int
    {
        if (is_numeric($value)) {
            $val = intval($value);
            if ($value === '0' && $val === 0) {
                return $val;
            } else if ($val !== 0) {
                return $val;
            }
        }
        throw new RuntimeCommandException(sprintf("Value %s is invalid for type int", $value));
    }
}