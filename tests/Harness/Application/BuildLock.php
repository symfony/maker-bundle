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

use Symfony\Component\Filesystem\Filesystem;

/**
 * flock-based build lock + build-into-temp-dir + atomic rename, so concurrent
 * first-run workers can never observe (or produce) a half-built directory.
 */
final class BuildLock
{
    /**
     * @param \Closure(string): void $build receives a temporary directory to build into
     */
    public static function buildAtomically(string $targetDir, string $expectedFingerprint, \Closure $build): void
    {
        $fs = new Filesystem();
        $fs->mkdir(\dirname($targetDir));

        $lockFile = $targetDir.'.lock';
        $handle = fopen($lockFile, 'c');
        if (!$handle || !flock($handle, \LOCK_EX)) {
            throw new \RuntimeException(\sprintf('Could not acquire build lock "%s".', $lockFile));
        }

        try {
            if (Stamp::isValid($targetDir, $expectedFingerprint)) {
                return;
            }

            $buildDir = \dirname($targetDir).'/.build-'.basename($targetDir).'-'.getmypid();
            $fs->remove($buildDir);

            try {
                $build($buildDir);
                $fs->remove($targetDir);
                $fs->rename($buildDir, $targetDir);
            } catch (\Throwable $e) {
                $fs->remove($buildDir);

                throw $e;
            }
        } finally {
            flock($handle, \LOCK_UN);
            fclose($handle);
        }
    }

    /**
     * Shared lock for READING a built directory, so a concurrent rebuild
     * (epoch rollover, catalog edit while paratest runs) cannot swap the
     * template out mid-copy.
     */
    public static function withSharedLock(string $targetDir, \Closure $read): mixed
    {
        $handle = fopen($targetDir.'.lock', 'c');
        if (!$handle || !flock($handle, \LOCK_SH)) {
            throw new \RuntimeException(\sprintf('Could not acquire shared build lock "%s.lock".', $targetDir));
        }

        try {
            return $read();
        } finally {
            flock($handle, \LOCK_UN);
            fclose($handle);
        }
    }
}
