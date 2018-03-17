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
    private static $classLoader;
    private $rootDir;

    public function __construct(string $rootDir)
    {
        $this->rootDir = $rootDir;
    }

    /**
     * Returns the relative path to where a new class should live.
     *
     * @param string $className
     *
     * @return null|string
     *
     * @throws \Exception
     */
    public function getPathForFutureClass(string $className)
    {
        // lookup is obviously modeled off of Composer's autoload logic
        foreach ($this->getClassLoader()->getPrefixesPsr4() as $prefix => $paths) {
            if (0 === strpos($className, $prefix)) {
                return $paths[0].'/'.str_replace('\\', '/', str_replace($prefix, '', $className)).'.php';
            }
        }

        foreach ($this->getClassLoader()->getPrefixes() as $prefix => $paths) {
            if (0 === strpos($className, $prefix)) {
                return $paths[0].'/'.str_replace('\\', '/', $className).'.php';
            }
        }

        if ($this->getClassLoader()->getFallbackDirsPsr4()) {
            return $this->getClassLoader()->getFallbackDirsPsr4()[0].'/'.str_replace('\\', '/', $className).'.php';
        }

        if ($this->getClassLoader()->getFallbackDirs()) {
            return $this->getClassLoader()->getFallbackDirs()[0].'/'.str_replace('\\', '/', $className).'.php';
        }

        return null;
    }

    public function getNamespacePrefixForClass(string $className): string
    {
        foreach ($this->getClassLoader()->getPrefixesPsr4() as $prefix => $paths) {
            if (0 === strpos($className, $prefix)) {
                return $prefix;
            }
        }

        return '';
    }

    private function getClassLoader(): ClassLoader
    {
        if (null === self::$classLoader) {
            $autoloadPath = $this->rootDir.'/vendor/autoload.php';

            if (!file_exists($autoloadPath)) {
                throw new \Exception(sprintf('Could not find the autoload file: "%s"', $autoloadPath));
            }

            self::$classLoader = require $autoloadPath;
        }

        return self::$classLoader;
    }
}
