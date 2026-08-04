<?php

/*
 * This file is part of the Symfony MakerBundle package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\MakerBundle\Tests\Harness\Application\Resetter;

use Symfony\Component\Filesystem\Filesystem;

/**
 * Portable pure-PHP fallback when no rsync/robocopy binary is available.
 * Mirrors trees with explicit mtime restoration.
 */
final class FilesystemResetter implements Resetter
{
    public function syncTree(string $from, string $to, array $excludes = []): void
    {
        $fs = new Filesystem();
        $fs->mkdir($to);

        $from = rtrim($from, '/');
        $to = rtrim($to, '/');
        $normalizedExcludes = array_map(static fn ($e) => trim($e, '/'), $excludes);

        $sourceEntries = $this->listTree($from, $normalizedExcludes);
        $targetEntries = $this->listTree($to, $normalizedExcludes);

        foreach (array_diff_key($targetEntries, $sourceEntries) as $relative => $type) {
            $fs->remove($to.'/'.$relative);
        }

        foreach ($sourceEntries as $relative => $type) {
            $sourcePath = $from.'/'.$relative;
            $targetPath = $to.'/'.$relative;

            if ('dir' === $type) {
                $fs->mkdir($targetPath);
                continue;
            }

            if ('link' === $type) {
                $linkTarget = readlink($sourcePath);
                if (!is_link($targetPath) || readlink($targetPath) !== $linkTarget) {
                    $fs->remove($targetPath);
                    try {
                        $fs->symlink($linkTarget, $targetPath);
                    } catch (\Throwable) {
                        // Windows without symlink privilege (composer created
                        // the source link as a junction): mirror the target
                        // instead of aborting the whole reset
                        $fs->mirror($linkTarget, $targetPath, options: ['override' => true]);
                    }
                }
                continue;
            }

            if (!is_file($targetPath) || filesize($targetPath) !== filesize($sourcePath) || md5_file($targetPath) !== md5_file($sourcePath)) {
                $fs->copy($sourcePath, $targetPath, true);
            }
            touch($targetPath, filemtime($sourcePath));
        }

        foreach ($sourceEntries as $relative => $type) {
            if ('dir' === $type) {
                touch($to.'/'.$relative, filemtime($from.'/'.$relative));
            }
        }
    }

    /**
     * @param list<string> $excludes
     *
     * @return array<string, 'dir'|'file'|'link'>
     */
    private function listTree(string $root, array $excludes): array
    {
        if (!is_dir($root)) {
            return [];
        }

        $entries = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST,
        );

        foreach ($iterator as $item) {
            $relative = substr($item->getPathname(), \strlen($root) + 1);
            $topLevel = explode('/', str_replace('\\', '/', $relative))[0];
            if (\in_array($topLevel, $excludes, true)) {
                continue;
            }

            $entries[$relative] = $item->isLink() ? 'link' : ($item->isDir() ? 'dir' : 'file');
        }

        return $entries;
    }
}
