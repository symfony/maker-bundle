<?php

/*
 * This file is part of the Symfony MakerBundle package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\MakerBundle\Maker\Common;

use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\Util\DependencyInstaller;

/**
 * @author Jesse Rushlow <jr@rushlow.dev>
 *
 * @internal
 */
trait InstallDependencyTrait
{
    /**
     * @param string $composerPackage Fully qualified composer package to install e.g. symfony/maker-bundle
     *
     * @throws \Symfony\Bundle\MakerBundle\Exception\RuntimeCommandException when composer fails
     */
    public function installDependencyIfNeeded(ConsoleStyle $io, string $expectedClassToExist, string $composerPackage): ConsoleStyle
    {
        if (!class_exists($expectedClassToExist)) {
            (new DependencyInstaller())->installPackage($io, $composerPackage);
        }

        return $io;
    }
}
