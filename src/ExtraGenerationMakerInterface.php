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
 * Interface to do extra code generation in a maker.
 *
 * @author Ryan Weaver <ryan@knpuniversity.com>
 */
interface ExtraGenerationMakerInterface
{
    /**
     * Called after normal code generation.
     */
    public function afterGenerate(ConsoleStyle $io, array $params);
}
