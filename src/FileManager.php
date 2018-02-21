<?php

/*
 * This file is part of the Symfony MakerBundle package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\MakerBundle;

use Composer\Autoload\ClassLoader;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 * @author Ryan Weaver <weaverryan@gmail.com>
 *
 * @internal
 */
class FileManager
{
    private $fs;
    private $rootDirectory;
    /** @var SymfonyStyle */
    private $io;

    private static $classLoader;

    public function __construct(Filesystem $fs, string $rootDirectory)
    {
        $this->fs = $fs;
        $this->rootDirectory = rtrim($this->realpath($this->normalizeSlashes($rootDirectory)), '/');
    }

    public function setIO(SymfonyStyle $io)
    {
        $this->io = $io;
    }

    public function parseTemplate(string $templatePath, array $parameters): string
    {
        ob_start();
        extract($parameters, EXTR_SKIP);
        include $templatePath;

        return ob_get_clean();
    }

    public function dumpFile(string $filename, string $content)
    {
        $this->fs->dumpFile($this->absolutizePath($filename), $content);
        $this->io->comment(sprintf('<fg=green>created</>: %s', $this->relativizePath($filename)));
    }

    public function fileExists($path): bool
    {
        return file_exists($this->absolutizePath($path));
    }

    /**
     * Attempts to make the path relative to the root directory.
     *
     * @param string $absolutePath
     *
     * @return string
     *
     * @throws \Exception
     */
    public function relativizePath($absolutePath): string
    {
        $absolutePath = $this->normalizeSlashes($absolutePath);

        // see if the path is even in the root
        if (false === strpos($absolutePath, $this->rootDirectory)) {
            return $absolutePath;
        }

        $absolutePath = $this->realPath($absolutePath);

        $relativePath = ltrim(str_replace($this->rootDirectory, '', $absolutePath), '/');
        if (0 === strpos($relativePath, './')) {
            $relativePath = substr($relativePath, 2);
        }

        return is_dir($absolutePath) ? rtrim($relativePath, '/').'/' : $relativePath;
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
                $path = $paths[0].'/'.str_replace('\\', '/', str_replace($prefix, '', $className)).'.php';

                return $this->relativizePath($path);
            }
        }

        foreach ($this->getClassLoader()->getPrefixes() as $prefix => $paths) {
            if (0 === strpos($className, $prefix)) {
                $path = $paths[0].'/'.str_replace('\\', '/', $className).'.php';

                return $this->relativizePath($path);
            }
        }

        if ($this->getClassLoader()->getFallbackDirsPsr4()) {
            $path = $this->getClassLoader()->getFallbackDirsPsr4()[0].'/'.str_replace('\\', '/', $className).'.php';

            return $this->relativizePath($path);
        }

        if ($this->getClassLoader()->getFallbackDirs()) {
            $path = $this->getClassLoader()->getFallbackDirs()[0].'/'.str_replace('\\', '/', $className).'.php';

            return $this->relativizePath($path);
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
            $autoloadPath = $this->absolutizePath('vendor/autoload.php');

            if (!file_exists($autoloadPath)) {
                throw new \Exception(sprintf('Could not find the autoload file: "%s"', $autoloadPath));
            }

            self::$classLoader = require $autoloadPath;
        }

        return self::$classLoader;
    }

    public function absolutizePath($path): string
    {
        if (0 === strpos($path, '/')) {
            return $path;
        }

        // support windows drive paths: C:\
        if (1 === strpos($path, ':\\')) {
            return $path;
        }

        return sprintf('%s/%s', $this->rootDirectory, $path);
    }

    /**
     * Resolve '../' in paths (like real_path), but for non-existent files.
     *
     * @param string $absolutePath
     *
     * @return string
     */
    private function realPath($absolutePath): string
    {
        $finalParts = [];
        $currentIndex = -1;

        $absolutePath = $this->normalizeSlashes($absolutePath);
        foreach (explode('/', $absolutePath) as $pathPart) {
            if ('..' === $pathPart) {
                // we need to remove the previous entry
                if (-1 === $currentIndex) {
                    throw new \Exception(sprintf('Problem making path relative - is the path "%s" absolute?', $absolutePath));
                }

                unset($finalParts[$currentIndex]);
                --$currentIndex;

                continue;
            }

            $finalParts[] = $pathPart;
            ++$currentIndex;
        }

        $finalPath = implode('/', $finalParts);
        // Normalize: // => /
        // Normalize: /./ => /
        $finalPath = str_replace('//', '/', $finalPath);
        $finalPath = str_replace('/./', '/', $finalPath);

        return $finalPath;
    }

    private function normalizeSlashes(string $path)
    {
        return str_replace('\\', '/', $path);
    }
}
