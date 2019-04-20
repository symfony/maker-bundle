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
 * @internal
 */
class ComposerAutoloaderFinder
{
    private $rootNamespace;

    /**
     * @var ClassLoader|null
     */
    private $classLoader = null;

    public function __construct(string $rootNamespace)
    {
        $this->rootNamespace = [
            'psr0' => rtrim($rootNamespace, '\\'),
            'psr4' => rtrim($rootNamespace, '\\').'\\',
        ];
    }

    public function getClassLoader(): ClassLoader
    {
        if (null === $this->classLoader) {
            $this->findComposerClassLoader();
        }

        if (null === $this->classLoader) {
            throw new \Exception("Could not find a Composer autoloader that autoloads from '{$this->rootNamespace['psr4']}'");
        }

        return $this->classLoader;
    }

    private function findComposerClassLoader()
    {
        $autoloadFunctions = spl_autoload_functions();

        foreach ($autoloadFunctions as $autoloader) {
            $classLoader = $this->extractComposerClassLoader($autoloader);
            if ($classLoader && $this->locateMatchingClassLoader($classLoader)) {
                return;
            }
        }
    }

    /**
     * @return ClassLoader|null
     */
    private function extractComposerClassLoader(array $autoloader)
    {
        if (isset($autoloader[0]) && \is_object($autoloader[0])) {
            if ($autoloader[0] instanceof ClassLoader) {
                return $autoloader[0];
            }
            if ($autoloader[0] instanceof DebugClassLoader
                && \is_array($autoloader[0]->getClassLoader())
                && $autoloader[0]->getClassLoader()[0] instanceof ClassLoader) {
                return $autoloader[0]->getClassLoader()[0];
            }
        }

        return null;
    }

    private function locateMatchingClassLoader(ClassLoader $classLoader): bool
    {
        foreach ($classLoader->getPrefixesPsr4() as $prefix => $paths) {
            // We can default to using the autoloader containing this component if none are matching.
            if ('Symfony\\Bundle\\MakerBundle\\' === $prefix) {
                $this->classLoader = $classLoader;
            }
            if (0 === strpos($this->rootNamespace['psr4'], $prefix)) {
                $this->classLoader = $classLoader;

                return true;
            }
        }

        foreach ($classLoader->getPrefixes() as $prefix => $paths) {
            if (0 === strpos($this->rootNamespace['psr0'], $prefix)) {
                $this->classLoader = $classLoader;

                return true;
            }
        }

        return false;
    }
}
