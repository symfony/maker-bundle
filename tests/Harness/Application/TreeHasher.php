<?php

/*
 * This file is part of the Symfony MakerBundle package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\MakerBundle\Tests\Harness\Application;

final class TreeHasher
{
    /**
     * Content hash of one or more directory trees. Deterministic (sorted),
     * does not follow symlinked directories.
     *
     * @param list<string> $paths files or directories
     */
    public static function hash(array $paths): string
    {
        $entries = [];
        foreach ($paths as $path) {
            if (is_file($path)) {
                $entries[basename($path)] = md5_file($path);
                continue;
            }
            if (!is_dir($path)) {
                continue;
            }

            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS),
            );
            foreach ($iterator as $file) {
                if (!$file->isFile()) {
                    continue;
                }
                $relative = substr($file->getPathname(), \strlen($path) + 1);
                $entries[basename($path).'/'.$relative] = md5_file($file->getPathname());
            }
        }

        ksort($entries);

        return md5(json_encode($entries));
    }
}
