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
 * Windows resetter. robocopy is built into Windows, mirrors trees (/MIR) and
 * preserves data + attributes + timestamps (/COPY:DAT). Exit codes 0-7 mean
 * success (robocopy encodes "what changed" in the exit code); >= 8 is failure.
 */
final class RobocopyResetter implements Resetter
{
    public static function isSupported(): bool
    {
        return '\\' === \DIRECTORY_SEPARATOR;
    }

    public function syncTree(string $from, string $to, array $excludes = []): void
    {
        (new Filesystem())->mkdir($to);

        // /SL and /SJ copy symlinks/junctions AS links (like rsync) instead of
        // traversing them — traversal would freeze the bundle projection link
        // into a snapshot and violates the never-follow-the-projection invariant
        $command = ['robocopy', rtrim($from, '/\\'), rtrim($to, '/\\'), '/MIR', '/COPY:DAT', '/DCOPY:DAT', '/SL', '/SJ', '/NFL', '/NDL', '/NJH', '/NJS', '/NP'];
        foreach ($excludes as $exclude) {
            $normalized = trim($exclude, '/');
            // dir vs file exclusion: trust the trailing-slash convention and
            // check BOTH trees — '/var/' must still protect the target's warm
            // cache when the (never-warmed) source template has no var/ at all
            if (str_ends_with($exclude, '/') || is_dir($from.'/'.$normalized) || is_dir($to.'/'.$normalized)) {
                $command[] = '/XD';
                $command[] = rtrim($from, '/\\').'\\'.$normalized;
                $command[] = rtrim($to, '/\\').'\\'.$normalized;
            } else {
                $command[] = '/XF';
                $command[] = $normalized;
            }
        }

        $process = ProcessRunner::run($command, \dirname($to), allowFailure: true, timeout: 300);
        if ($process->getExitCode() >= 8) {
            throw new \RuntimeException(\sprintf('robocopy failed (exit %d) mirroring "%s" -> "%s":\n%s', $process->getExitCode(), $from, $to, ProcessRunner::tail($process->getOutput().$process->getErrorOutput())));
        }
    }
}
