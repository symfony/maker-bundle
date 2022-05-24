<?php

/*
 * This file is part of the Symfony MakerBundle package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\MakerBundle\Util;

use Composer\Autoload\ClassLoader;

/**
 * @author Ryan Weaver <weaverryan@gmail.com>
 *
 * @internal
 */
class AutoloaderUtil
{
    public function __construct(
        private ComposerAutoloaderFinder $autoloaderFinder,
    ) {
    }

    /**
     * Returns the relative path to where a new class should live.
     *
     * @throws \Exception
     */
    public function getPathForFutureClass(string $className): ?string
    {
        $classLoader = $this->getClassLoader();

        // lookup is obviously modeled off of Composer's autoload logic
        foreach ($classLoader->getPrefixesPsr4() as $prefix => $paths) {
            if (str_starts_with($className, $prefix)) {
                return $paths[0].'/'.str_replace('\\', '/', substr($className, \strlen($prefix))).'.php';
            }
        }

        foreach ($classLoader->getPrefixes() as $prefix => $paths) {
            if (str_starts_with($className, $prefix)) {
                return $paths[0].'/'.str_replace('\\', '/', $className).'.php';
            }
        }

        if ($classLoader->getFallbackDirsPsr4()) {
            return $classLoader->getFallbackDirsPsr4()[0].'/'.str_replace('\\', '/', $className).'.php';
        }

        if ($classLoader->getFallbackDirs()) {
            return $classLoader->getFallbackDirs()[0].'/'.str_replace('\\', '/', $className).'.php';
        }

        return null;
    }

    public function getNamespacePrefixForClass(string $className): string
    {
        foreach ($this->getClassLoader()->getPrefixesPsr4() as $prefix => $paths) {
            if (str_starts_with($className, $prefix)) {
                return $prefix;
            }
        }

        return '';
    }

    /**
     * Returns if the namespace is configured by composer autoloader.
     */
    public function isNamespaceConfiguredToAutoload(string $namespace): bool
    {
        $namespace = trim($namespace, '\\').'\\';
        $classLoader = $this->getClassLoader();

        foreach ($classLoader->getPrefixesPsr4() as $prefix => $paths) {
            if (str_starts_with($namespace, $prefix)) {
                return true;
            }
        }

        foreach ($classLoader->getPrefixes() as $prefix => $paths) {
            if (str_starts_with($namespace, $prefix)) {
                return true;
            }
        }

        return false;
    }

    private function getClassLoader(): ClassLoader
    {
        return $this->autoloaderFinder->getClassLoader();
    }
}
