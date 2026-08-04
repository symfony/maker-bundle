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

use Symfony\Bundle\MakerBundle\Tests\Harness\Process\ProcessRunner;
use Symfony\Component\Filesystem\Filesystem;

/**
 * rsync -a preserves mtimes (rounding down, which is the safe direction for
 * Symfony's FileResource::isFresh()) and copies symlinks as links.
 */
final class RsyncResetter implements Resetter
{
    public static function isSupported(): bool
    {
        static $supported = null;

        if (null === $supported) {
            exec('rsync --version 2>/dev/null', $output, $exitCode);
            $supported = 0 === $exitCode;
        }

        return $supported;
    }

    public function syncTree(string $from, string $to, array $excludes = []): void
    {
        (new Filesystem())->mkdir($to);

        $command = ['rsync', '-a', '--delete'];
        foreach ($excludes as $exclude) {
            $command[] = '--exclude='.$exclude;
        }
        $command[] = rtrim($from, '/').'/';
        $command[] = rtrim($to, '/').'/';

        ProcessRunner::run($command, \dirname($to));
    }
}
