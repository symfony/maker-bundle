<?php

/*
 * This file is part of the Symfony MakerBundle package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Rector\Config\RectorConfig;
use Rector\Php74\Rector\Closure\ClosureToArrowFunctionRector;
use Rector\Php80\Rector\Catch_\RemoveUnusedVariableInCatchRector;
use Rector\Php81\Rector\FuncCall\NullToStrictStringFuncCallArgRector;
use Rector\Php81\Rector\Property\ReadOnlyPropertyRector;
use Rector\Set\ValueObject\LevelSetList;

return static function (RectorConfig $config): void {
    $config->paths([
        __DIR__.'/src',
    ]);

    $config->rules([
        // Remove when LevelSet is fully enabled
        ClosureToArrowFunctionRector::class,
        RemoveUnusedVariableInCatchRector::class,
    ]);

    $config->sets([
//        LevelSetList::UP_TO_PHP_81,
    ]);

    $config->skip([
        NullToStrictStringFuncCallArgRector::class,
        ReadOnlyPropertyRector::class,
    ]);
};
