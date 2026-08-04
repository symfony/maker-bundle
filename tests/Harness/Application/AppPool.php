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
 * One live app per (worker, profile), warmed AT ITS FINAL PATH — compiled
 * containers bake absolute paths, so caches are never moved across
 * directories. The pristine var/cache is snapshotted as a worker-local
 * baseline right after warming.
 *
 * Reset lifecycle (strict): a pristine baseline cache is only ever restored
 * immediately after sources returned to baseline. Mid-test invalidation is a
 * DELETION concern and lives in GeneratedApp, never here.
 */
final class AppPool
{
    /**
     * Prepares (or resets) the worker app for the given profile and returns
     * its directory.
     */
    public static function preparedAppDir(string $profileName): string
    {
        BundleProjection::ensure();

        $profile = ProfileCatalog::get($profileName);
        $fingerprint = ProfileBuilder::ensure($profile);

        $workerDir = Paths::workerDir($profileName);
        $baselineDir = Paths::baselineDir($profileName);
        $info = self::readInfo($profileName);

        if (null === $info || ($info['profileFingerprint'] ?? null) !== $fingerprint) {
            self::materialize($profileName, $fingerprint);

            return $workerDir;
        }

        self::resetSources($profileName);

        if (($info['containerShape'] ?? null) !== BundleProjection::containerShapeFingerprint()) {
            self::rewarm($profileName, $fingerprint);
        } else {
            // var/cache is restored INCREMENTALLY: the resetters mirror with
            // deletion and preserve mtimes, so an untouched cache costs a
            // stat-walk instead of a full copy of the baseline every test
            $fs = new Filesystem();
            foreach (glob($workerDir.'/var/*') ?: [] as $entry) {
                if ('cache' !== basename($entry)) {
                    $fs->remove($entry);
                }
            }
            ResetterFactory::best()->syncTree($baselineDir, $workerDir.'/var/cache');
        }

        return $workerDir;
    }

    public static function markVendorDirty(string $profileName): void
    {
        $fs = new Filesystem();
        $fs->mkdir(Paths::markersDir());
        $fs->touch(Paths::vendorDirtyMarker($profileName));
    }

    private static function materialize(string $profileName, string $fingerprint): void
    {
        $fs = new Filesystem();
        $workerDir = Paths::workerDir($profileName);
        $baselineDir = Paths::baselineDir($profileName);

        $fs->remove([$workerDir, $baselineDir, self::infoFile($profileName), Paths::vendorDirtyMarker($profileName)]);

        BuildLock::withSharedLock(Paths::profileDir($profileName), static function () use ($profileName, $workerDir): void {
            ResetterFactory::best()->syncTree(Paths::profileDir($profileName), $workerDir, ['/'.Stamp::FILENAME]);
        });

        // the copy itself can degrade the vendor link (see ProfileBuilder)
        BundleProjection::repairVendorLink($workerDir);
        self::warm($workerDir);
        ResetterFactory::best()->syncTree($workerDir.'/var/cache', $baselineDir);
        self::writeInfo($profileName, $fingerprint);
    }

    private static function resetSources(string $profileName): void
    {
        $workerDir = Paths::workerDir($profileName);
        $templateDir = Paths::profileDir($profileName);
        $resetter = ResetterFactory::best();
        $marker = Paths::vendorDirtyMarker($profileName);

        BuildLock::withSharedLock($templateDir, static function () use ($resetter, $templateDir, $workerDir, $marker): void {
            if (file_exists($marker)) {
                // Full restore including vendor/, then the marker is consumed.
                // The bundle projection link comes back with the template's
                // vendor/ tree — and is re-verified, since the copy itself can
                // degrade it (see ProfileBuilder).
                $resetter->syncTree($templateDir, $workerDir, ['/var/', '/'.Stamp::FILENAME]);
                BundleProjection::repairVendorLink($workerDir);
                (new Filesystem())->remove($marker);

                return;
            }

            $resetter->syncTree($templateDir, $workerDir, ['/vendor/', '/var/', '/'.Stamp::FILENAME]);
            // composer dump-autoload during a test mutates vendor/composer/*
            $resetter->syncTree($templateDir.'/vendor/composer', $workerDir.'/vendor/composer');
        });
    }

    private static function rewarm(string $profileName, string $fingerprint): void
    {
        $fs = new Filesystem();
        $workerDir = Paths::workerDir($profileName);

        $fs->remove([$workerDir.'/var', Paths::baselineDir($profileName)]);
        self::warm($workerDir);
        ResetterFactory::best()->syncTree($workerDir.'/var/cache', Paths::baselineDir($profileName));
        self::writeInfo($profileName, $fingerprint);
    }

    private static function warm(string $appDir): void
    {
        // Makers run against the dev container without freshness checks
        // (APP_DEBUG=0); nested test runs keep debug on so the test container
        // self-invalidates when makers add classes.
        ProcessRunner::run('php bin/console cache:warmup --env=dev --no-debug', $appDir, timeout: 120);
        ProcessRunner::run('php bin/console cache:warmup --env=test', $appDir, timeout: 120);

        // filemtime() is whole-second: a file a maker writes within the same
        // second as the warmed cache is not "newer" than it, so mtime-based
        // freshness checks (the 6.x skeleton tracks src/Controller routes via
        // DirectoryResource) keep the stale cache and 404 the new route. Only
        // the FIRST test after a warm is exposed (resets restore baseline
        // caches with their old mtimes) so wait out the warm-up's second.
        // 7.3+ skeletons route via `routing.controllers` (a GlobResource that
        // hashes the file list) and are immune, so they skip the wait.
        if (str_starts_with(Paths::symfonyVersionConstraint(), '6.')) {
            $warmedAt = time();
            while (time() === $warmedAt) {
                usleep(50_000);
            }
        }
    }

    private static function infoFile(string $profileName): string
    {
        return Paths::baselineDir($profileName).'.materialized.json';
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function readInfo(string $profileName): ?array
    {
        $file = self::infoFile($profileName);
        if (!is_file($file)) {
            return null;
        }

        $data = json_decode(file_get_contents($file), true);

        return \is_array($data) ? $data : null;
    }

    private static function writeInfo(string $profileName, string $fingerprint): void
    {
        (new Filesystem())->mkdir(\dirname(self::infoFile($profileName)));

        file_put_contents(self::infoFile($profileName), json_encode([
            'profileFingerprint' => $fingerprint,
            'containerShape' => BundleProjection::containerShapeFingerprint(),
        ], \JSON_PRETTY_PRINT));
    }
}
