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
use Symfony\Component\Debug\DebugClassLoader;

/**
 * @author Ryan Weaver <weaverryan@gmail.com>
 *
 * @internal
 */
class AutoloaderUtil
{
    /**
     * @var ClassLoader
     */
    private $classLoader;

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
        if (null === $this->classLoader) {
            $autoloadFunctions = spl_autoload_functions();
            foreach ($autoloadFunctions as $autoloader) {
                if (is_array($autoloader) && isset($autoloader[0]) && is_object($autoloader[0])) {
                    if ($autoloader[0] instanceof ClassLoader) {
                        $this->classLoader = $autoloader[0];
                        break;
                    }
                    if ($autoloader[0] instanceof DebugClassLoader
                        && is_array($autoloader[0]->getClassLoader())
                        && $autoloader[0]->getClassLoader()[0] instanceof ClassLoader) {
                        $this->classLoader = $autoloader[0]->getClassLoader()[0];
                        break;
                    }
                }
            }
            if (null === $this->classLoader) {
                throw new \Exception('Composer ClassLoader not found!');
            }
        }

        return $this->classLoader;
    }
}
