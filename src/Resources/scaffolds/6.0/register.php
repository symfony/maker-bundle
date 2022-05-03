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
    'description' => 'Create registration form and tests.',
    'dependents' => [
        'auth',
    ],
    'packages' => [
        'symfony/form' => 'all',
    ],
];
