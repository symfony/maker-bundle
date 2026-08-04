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

use Symfony\Bundle\MakerBundle\Tests\Harness\Application\Resetter\ResetterFactory;
use Symfony\Bundle\MakerBundle\Tests\Harness\Process\ProcessRunner;
use Symfony\Component\Filesystem\Filesystem;

/**
 * How the current checkout appears inside every generated app.
 *
 * A surface-only projection (never the checkout root: the checkout contains
 * tests/tmp/workers/..., whose apps link back here — linking the root would
 * create a recursive filesystem graph):
 *
 *   tests/tmp/bundle/current/
 *     composer.json            (copied)
 *     src/       -> <checkout>/src/
 *     config/    -> <checkout>/config/
 *     templates/ -> <checkout>/templates/
 *
 * vendor/symfony/maker-bundle in every app resolves to this directory via a
 * Composer path repository, so edits to the checkout apply live.
 *
 * Where symlinks are unavailable (Windows without developer mode), the
 * exported directories are MIRRORED instead, and refreshed whenever their
 * content fingerprints change.
 */
final class BundleProjection
{
    /**
     * The exported package surface. If membership stops being hard-coded,
     * whatever file controls it must join the fingerprints below.
     */
    private const LINKED_DIRS = ['src', 'config', 'templates'];

    private const STAMP_FILE = '.projection-stamp.json';

    private static bool $ensured = false;

    public static function ensure(): void
    {
        if (self::$ensured) {
            return;
        }

        $fs = new Filesystem();
        $dir = Paths::bundleProjectionDir();
        $fs->mkdir(\dirname($dir));

        // Concurrent first-run paratest workers all pass here: serialize the
        // mutation; every worker re-checks state under the lock.
        $lockHandle = fopen(\dirname($dir).'/.projection.lock', 'c');
        if (!$lockHandle || !flock($lockHandle, \LOCK_EX)) {
            throw new \RuntimeException('Could not acquire the bundle projection lock.');
        }

        try {
            self::build($dir);
        } finally {
            flock($lockHandle, \LOCK_UN);
            fclose($lockHandle);
        }

        self::$ensured = true;
    }

    private static function build(string $dir): void
    {
        $fs = new Filesystem();
        $root = Paths::rootPath();
        $fs->mkdir($dir);

        $stampFile = $dir.'/'.self::STAMP_FILE;
        $stamp = is_file($stampFile) ? (json_decode(file_get_contents($stampFile), true) ?: []) : [];
        $mode = $stamp['mode'] ?? null;

        $expected = [
            'projection' => self::projectionFingerprint(),
            'containerShape' => self::containerShapeFingerprint(),
            'composerJson' => self::dependencyInputHash(),
        ];

        // decide the mode ONCE, before touching any dir: a per-dir fallback
        // could leave a half-links, half-mirror projection whose stamp lies
        // about the mirrored part (and it would then never refresh)
        if ('mirror' !== $mode) {
            $probe = $dir.'/.probe-link';
            $fs->remove($probe);
            try {
                $fs->symlink($root.'/src', $probe);
                $mode = 'symlink';
            } catch (\Throwable) {
                // Windows without symlink privilege: mirror the whole surface,
                // refreshed by fingerprint below
                $mode = 'mirror';
            } finally {
                $fs->remove($probe);
            }
        }

        foreach (self::LINKED_DIRS as $linked) {
            $target = $root.'/'.$linked;
            $link = $dir.'/'.$linked;

            if ('mirror' === $mode) {
                self::mirrorIfStale($stamp, $expected, $target, $link);
                continue;
            }

            if (is_link($link) && readlink($link) === $target) {
                continue;
            }

            $fs->remove($link);
            $fs->symlink($target, $link);
        }

        $composerJson = $root.'/composer.json';
        $copy = $dir.'/composer.json';
        if (!is_file($copy) || md5_file($copy) !== md5_file($composerJson)) {
            copy($composerJson, $copy);
        }

        file_put_contents($stampFile, json_encode($expected + ['mode' => $mode], \JSON_PRETTY_PRINT));
    }

    /**
     * @param array<string, string> $stamp
     * @param array<string, string> $expected
     */
    private static function mirrorIfStale(array $stamp, array $expected, string $source, string $destination): void
    {
        $fresh = is_dir($destination)
            && ($stamp['projection'] ?? null) === $expected['projection']
            && ($stamp['containerShape'] ?? null) === $expected['containerShape'];

        if (!$fresh) {
            ResetterFactory::best()->syncTree($source, $destination);
        }
    }

    /**
     * App templates carry vendor/symfony/maker-bundle as a symlink (or NTFS
     * junction) to the projection. Tar-based CI caches do not reliably
     * round-trip Windows junctions, so a restored template can pass its stamp
     * check while its bundle link is dead. Recreate the link when it no longer
     * resolves; if that is impossible, drop the stamp so the template is
     * rebuilt from scratch (composer then creates its own junction, which
     * needs no special privilege).
     */
    public static function repairVendorLink(string $templateDir): void
    {
        $link = $templateDir.'/vendor/symfony/maker-bundle';
        if (!is_dir($templateDir.'/vendor')) {
            return;
        }

        self::ensure();

        // The invariant is IDENTITY, not resolution: the entry must BE a link
        // onto the projection. Cache-restored trees come back in shapes that
        // fool any content probe (tar materializes the junction as a real
        // directory — sometimes partially, sometimes with composer.json intact
        // while deeper symlinks are dead), so anything that does not resolve
        // to the projection itself is torn down and relinked.
        $projection = realpath(Paths::bundleProjectionDir());
        if (false !== $projection && realpath($link) === $projection && is_file($link.'/src/MakerBundle.php')) {
            return;
        }

        $fs = new Filesystem();
        $fs->remove($link);

        try {
            if ('\\' === \DIRECTORY_SEPARATOR) {
                // a junction, like composer itself creates for path repos:
                // works without the symlink privilege PHP symlink() needs
                ProcessRunner::run(
                    ['cmd', '/c', 'mklink', '/J', str_replace('/', '\\', $link), str_replace('/', '\\', Paths::bundleProjectionDir())],
                    $templateDir,
                    allowFailure: true,
                );
            } else {
                $fs->symlink(Paths::bundleProjectionDir(), $link);
            }
        } catch (\Throwable) {
        }

        if (!is_file($link.'/src/MakerBundle.php')) {
            // last resort where links do not survive at all: REAL files,
            // copied straight from the checkout (the projection's own dirs
            // may be symlinks). Immune to any reparse-point semantics; and
            // because the identity check above never accepts a real
            // directory, every future repair re-syncs it fresh.
            $fs->remove($link);
            $fs->mkdir($link);
            copy(Paths::rootPath().'/composer.json', $link.'/composer.json');
            foreach (self::LINKED_DIRS as $dir) {
                ResetterFactory::best()->syncTree(Paths::rootPath().'/'.$dir, $link.'/'.$dir);
            }
        }

        if (!is_file($link.'/src/MakerBundle.php')) {
            $fs->remove($templateDir.'/'.Stamp::FILENAME);
        }
    }

    /**
     * A change here refreshes the projection only — generation templates are
     * not compiled into any container, so no rewarm is needed.
     */
    public static function projectionFingerprint(): string
    {
        static $hash = null;

        return $hash ??= TreeHasher::hash([Paths::rootPath().'/templates', __FILE__]);
    }

    /**
     * A change here means the compiled service graph of the generated apps may
     * differ: refresh the projection AND rewarm worker baseline caches.
     */
    public static function containerShapeFingerprint(): string
    {
        static $hash = null;

        return $hash ??= TreeHasher::hash([Paths::rootPath().'/src', Paths::rootPath().'/config']);
    }

    /**
     * The bundle's composer.json feeds Composer resolution and autoload
     * generation — a dependency input, not merely a container-shape input.
     * A change rebuilds profile templates and rematerializes workers.
     */
    public static function dependencyInputHash(): string
    {
        static $hash = null;

        return $hash ??= md5_file(Paths::rootPath().'/composer.json');
    }
}
