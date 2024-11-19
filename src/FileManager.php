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

use Symfony\Bundle\MakerBundle\Util\AutoloaderUtil;
use Symfony\Bundle\MakerBundle\Util\MakerFileLinkFormatter;
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
    private ?SymfonyStyle $io = null;

    public function __construct(
        private Filesystem $fs,
        private AutoloaderUtil $autoloaderUtil,
        private MakerFileLinkFormatter $makerFileLinkFormatter,
        private string $rootDirectory,
        private ?string $twigDefaultPath = null,
    ) {
        $this->rootDirectory = rtrim($this->realPath($this->normalizeSlashes($rootDirectory)), '/');
        $this->twigDefaultPath = $twigDefaultPath ? rtrim($this->relativizePath($twigDefaultPath), '/') : null;
    }

    public function setIO(SymfonyStyle $io): void
    {
        $this->io = $io;
    }

    public function parseTemplate(string $templatePath, array $parameters): string
    {
        ob_start();
        extract($parameters, \EXTR_SKIP);
        include $templatePath;

        return ob_get_clean();
    }

    public function dumpFile(string $filename, string $content): void
    {
        $absolutePath = $this->absolutizePath($filename);
        $newFile = !$this->fileExists($filename);
        $existingContent = $newFile ? '' : file_get_contents($absolutePath);

        $comment = $newFile ? '<fg=blue>created</>' : '<fg=yellow>updated</>';
        if ($existingContent === $content) {
            $comment = '<fg=green>no change</>';
        }

        $this->fs->dumpFile($absolutePath, $content);
        $relativePath = $this->relativizePath($filename);

        $this->io?->comment(\sprintf(
            '%s: %s',
            $comment,
            $this->makerFileLinkFormatter->makeLinkedPath($absolutePath, $relativePath)
        ));
    }

    public function fileExists($path): bool
    {
        return file_exists($this->absolutizePath($path));
    }

    /**
     * Attempts to make the path relative to the root directory.
     *
     * @throws \Exception
     */
    public function relativizePath(string $absolutePath): string
    {
        $absolutePath = $this->normalizeSlashes($absolutePath);

        // see if the path is even in the root
        if (!str_contains($absolutePath, $this->rootDirectory)) {
            return $absolutePath;
        }

        $absolutePath = $this->realPath($absolutePath);

        // str_replace but only the first occurrence
        $relativePath = ltrim(implode('', explode($this->rootDirectory, $absolutePath, 2)), '/');
        if (str_starts_with($relativePath, './')) {
            $relativePath = substr($relativePath, 2);
        }

        return is_dir($absolutePath) ? rtrim($relativePath, '/').'/' : $relativePath;
    }

    public function getFileContents(string $path): string
    {
        if (!$this->fileExists($path)) {
            throw new \InvalidArgumentException(\sprintf('Cannot find file "%s"', $path));
        }

        return file_get_contents($this->absolutizePath($path));
    }

    public function isPathInVendor(string $path): bool
    {
        return str_starts_with(
            $this->normalizeSlashes($path),
            $this->normalizeSlashes($this->rootDirectory.'/vendor/')
        );
    }

    public function absolutizePath($path): string
    {
        if (str_starts_with($path, '/')) {
            return $path;
        }

        // support windows drive paths: C:\ or C:/
        if (1 === strpos($path, ':\\') || 1 === strpos($path, ':/')) {
            return $path;
        }

        return \sprintf('%s/%s', $this->rootDirectory, $path);
    }

    /**
     * @throws \Exception
     */
    public function getRelativePathForFutureClass(string $className): ?string
    {
        $path = $this->autoloaderUtil->getPathForFutureClass($className);

        return null === $path ? null : $this->relativizePath($path);
    }

    public function getNamespacePrefixForClass(string $className): string
    {
        return $this->autoloaderUtil->getNamespacePrefixForClass($className);
    }

    public function isNamespaceConfiguredToAutoload(string $namespace): bool
    {
        return $this->autoloaderUtil->isNamespaceConfiguredToAutoload($namespace);
    }

    public function getRootDirectory(): string
    {
        return $this->rootDirectory;
    }

    public function getPathForTemplate(string $filename): string
    {
        if (null === $this->twigDefaultPath) {
            throw new \RuntimeException('Cannot get path for template: is Twig installed?');
        }

        return $this->twigDefaultPath.'/'.$filename;
    }

    /**
     * Resolve '../' in paths (like real_path), but for non-existent files.
     */
    private function realPath(string $absolutePath): string
    {
        $finalParts = [];
        $currentIndex = -1;

        $absolutePath = $this->normalizeSlashes($absolutePath);
        foreach (explode('/', $absolutePath) as $pathPart) {
            if ('..' === $pathPart) {
                // we need to remove the previous entry
                if (-1 === $currentIndex) {
                    throw new \Exception(\sprintf('Problem making path relative - is the path "%s" absolute?', $absolutePath));
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
        return str_replace(['//', '/./'], '/', $finalPath);
    }

    private function normalizeSlashes(string $path): string
    {
        return str_replace('\\', '/', $path);
    }
}
