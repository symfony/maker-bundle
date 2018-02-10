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

use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 * @author Ryan Weaver <weaverryan@gmail.com>
 *
 * @internal
 */
final class FileManager
{
    private $fs;
    private $rootDirectory;
    /** @var SymfonyStyle */
    private $io;

    public function __construct(Filesystem $fs, string $rootDirectory)
    {
        $this->fs = $fs;
        $this->rootDirectory = $rootDirectory;
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

    public function relativizePath($absolutePath): string
    {
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

    public function getPathForFutureClass(string $className): ?string
    {
        $autoloadPath = $this->absolutizePath('vendor/autoload.php');
            if (!file_exists($autoloadPath)) {
                throw new \Exception(sprintf('Could not find the autoload file: "%s"', $autoloadPath));
            }

            /** @var \Composer\Autoload\ClassLoader $loader */
            $loader = require $autoloadPath;
            $path = null;
            foreach ($loader->getPrefixesPsr4() as $prefix => $paths) {
                if (0 === strpos($className, $prefix)) {
                    return $paths[0].'/'.str_replace('\\', '/', str_replace($prefix, '', $className)).'.php';
                }
            }

            return null;
    }

    private function absolutizePath($path): string
    {
        if (0 === strpos($path, '/')) {
            return $path;
        }

        return sprintf('%s/%s', $this->rootDirectory, $path);
    }

    /**
     * Resolve '../' in paths (like real_path), but for non-existent files.
     *
     * @param string $absolutePath
     * @return string
     */
    private function realPath($absolutePath): string
    {
        $finalParts = [];
        $currentIndex = -1;

        foreach (explode('/', $absolutePath) as $pathPart) {
            if ('..' === $pathPart) {
                // we need to remove the previous entry
                if (-1 === $currentIndex) {
                    throw new \Exception(sprintf('Problem making path relative - is the path "%s" absolute?', $absolutePath));
                }

                unset($finalParts[$currentIndex]);
                $currentIndex--;

                continue;
            }

            $finalParts[] = $pathPart;
            $currentIndex++;
        }

        $finalPath = implode('/', $finalParts);
        // Normalize: // => /
        // Normalize: /./ => /
        $finalPath = str_replace('//', '/', $finalPath);
        $finalPath = str_replace('/./', '/', $finalPath);

        return $finalPath;
    }
}

