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

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 * @author Ryan Weaver <weaverryan@gmail.com>
 */
class Validator
{
    public static function validateClassName(string $className, string $errorMessage = '') : void
    {
        if (!preg_match('/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/', $className)) {
            $errorMessage = $errorMessage ?: sprintf('"%s" is not valid as a PHP class name (it must start with a letter or underscore, followed by any number of letters, numbers, or underscores)', $className);

            throw new \RuntimeException($errorMessage);
        }
    }
}
