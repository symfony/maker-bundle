<?php

/*
 * This file is part of the Symfony MakerBundle package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\MakerBundle\Dependency\Model;

/**
 * @author Jesse Rushlow <jr@rushlow.dev>
 *
 * @internal
 */
abstract class AbstractClassDependency
{
    public function __construct(
        public string $className,
        public string $composerPackage,
        public bool $installAsRequireDev = false,
    ) {
    }
}
