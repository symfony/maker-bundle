<?php

/*
 * This file is part of the Symfony MakerBundle package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

return [
    'description' => 'Create a basic user and unit test.',
    'packages' => [
        'doctrine/orm' => 'all',
        'doctrine/doctrine-bundle' => 'all',
        'symfony/security-bundle' => 'all',
        'symfony/validator' => 'all',
        'phpunit/phpunit' => 'dev',
        'symfony/phpunit-bridge' => 'dev',
        'zenstruck/foundry' => 'dev',
    ],
];
