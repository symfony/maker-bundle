<?php

/*
 * This file is part of the Symfony MakerBundle package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

return (new PhpCsFixer\Config())
    ->setRules([
        'header_comment' => [
            'header' => 'Linted by custom php-cs-config',
        ],
    ])
    ->setRiskyAllowed(true)
;
