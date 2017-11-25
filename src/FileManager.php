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

use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 * @author Ryan Weaver <weaverryan@gmail.com>
 * @author Yonel Ceruto <yonelceruto@gmail.com>
 */
class FileManager
{
    private $fs;
    private $rootDir;
    private $targetDir;
    /** @var SymfonyStyle */
    private $io;

    public function __construct(Filesystem $fs, string $rootDir, string $targetDir = null)
    {
        $this->fs = $fs;
        $this->rootDir = $rootDir;
        $this->targetDir = $targetDir ?: getcwd();
    }

    public function setIO(SymfonyStyle $io): void
    {
        $this->io = $io;
    }

    public function parseTemplate(string $templatePath, array $parameters): string
    {
        $keys = array_keys($parameters);
        $values = array_values($parameters);
        $placeholders = array_map(function ($name) {
            return "{{ $name }}";
        }, $keys);

        return str_replace($placeholders, $values, $this->loadTemplate($templatePath));
    }

    public function dumpFile(string $filename, string $content): void
    {
        $this->fs->dumpFile($this->absolutizePath($filename), $content);
        $this->io->comment(sprintf('<fg=green>created</>: %s', $this->relativizePath($filename)));
    }

    public function fileExists($path): bool
    {
        return file_exists($this->absolutizePath($path));
    }

    public function loadTemplate(string $templateName): string
    {
        if ($this->isAbsolutePath($templateName)) {
            return file_get_contents($templateName);
        }
        $paths = [
            $this->rootDir.'/Resources/MakerBundle/skeleton/',
            __DIR__.'/Resources/skeleton/',
        ];
        foreach ($paths as $path) {
            if (is_file($path.$templateName)) {
                return file_get_contents($path.$templateName);
            }
        }
        throw new \InvalidArgumentException(sprintf('Unable to find skeleton template "%s" (looked into: %s).', $templateName, implode(', ', $paths)));
    }

    private function isAbsolutePath($file): bool
    {
        return strspn($file, '/\\', 0, 1)
            || (strlen($file) > 3 && ctype_alpha($file[0])
                && ':' === $file[1]
                && strspn($file, '/\\', 2, 1)
            )
            || null !== parse_url($file, PHP_URL_SCHEME)
            ;
    }

    private function absolutizePath($path): string
    {
        if (0 === strpos($path, '/')) {
            return $path;
        }

        return sprintf('%s/%s', $this->targetDir, $path);
    }

    private function relativizePath($absolutePath): string
    {
        $relativePath = str_replace($this->targetDir, '.', $absolutePath);

        return is_dir($absolutePath) ? rtrim($relativePath, '/').'/' : $relativePath;
    }
}
