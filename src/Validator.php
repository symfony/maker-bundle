<?php

/*
 * This file is part of the Symfony package.
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
 */
final class Validator
{
    public static function validateClassName(string $className, string $errorMessage = '')
    {
        if (!preg_match('/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/', $className)) {
            $errorMessage = $errorMessage ?: sprintf('"%s" is not valid as a PHP class name (it must start with a letter or underscore, followed by any number of letters, numbers, or underscores)', $className);

            throw new RuntimeCommandException($errorMessage);
        }
    }

    public static function notBlank(string $value = null): string
    {
        if (null === $value || '' === $value) {
            throw new RuntimeCommandException('This value should not be blank');
        }

        return $value;
    }
}
