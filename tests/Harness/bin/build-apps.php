#!/usr/bin/env php
<?php

/*
 * This file is part of the Symfony MakerBundle package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/*
 * Prebuilds / validates / cleans the generated-app templates used by the
 * functional test suite.
 *
 *   php tests/Harness/bin/build-apps.php --all        build projection, skeleton and every profile
 *   php tests/Harness/bin/build-apps.php --validate   --all + assert a freshly prepared app boots without recompiling
 *   php tests/Harness/bin/build-apps.php --clean      remove all built state (kept/ is preserved)
 */

use Symfony\Bundle\MakerBundle\Tests\Harness\Application\AppPool;
use Symfony\Bundle\MakerBundle\Tests\Harness\Application\BundleProjection;
use Symfony\Bundle\MakerBundle\Tests\Harness\Application\Paths;
use Symfony\Bundle\MakerBundle\Tests\Harness\Application\ProfileBuilder;
use Symfony\Bundle\MakerBundle\Tests\Harness\Application\ProfileCatalog;
use Symfony\Bundle\MakerBundle\Tests\Harness\Application\Profiles;
use Symfony\Bundle\MakerBundle\Tests\Harness\Application\SkeletonBuilder;
use Symfony\Bundle\MakerBundle\Tests\Harness\Process\ProcessRunner;
use Symfony\Component\Filesystem\Filesystem;

require dirname(__DIR__, 3).'/vendor/autoload.php';

$mode = $argv[1] ?? '--all';

$step = static function (string $label, Closure $work) {
    $start = microtime(true);
    echo str_pad($label.' ', 60, '.');
    $result = $work();
    printf(" done (%.1fs)\n", microtime(true) - $start);

    return $result;
};

if ('--clean' === $mode) {
    $fs = new Filesystem();
    foreach (['bundle', 'skeleton', 'profiles', 'workers', 'baselines', 'markers'] as $dir) {
        $step('remove tests/tmp/'.$dir, static fn () => $fs->remove(Paths::tmpPath().'/'.$dir));
    }
    exit(0);
}

if (!in_array($mode, ['--all', '--validate'], true)) {
    fwrite(\STDERR, "Usage: build-apps.php [--all|--validate|--clean]\n");
    exit(1);
}

$step('bundle projection', static fn () => BundleProjection::ensure());
$step('skeleton ('.(Paths::symfonyVersionConstraint() ?: 'latest').')', static fn () => SkeletonBuilder::ensure());

$profiles = ProfileCatalog::all();
if (filter_var(getenv('MAKER_SKIP_PANTHER_TEST'), \FILTER_VALIDATE_BOOL)) {
    // its tests are skipped in this environment anyway; skipping the build
    // also skips the browser-driver download (cold time + a network risk)
    unset($profiles[Profiles::PANTHER]);
    echo "profile panther ... skipped (MAKER_SKIP_PANTHER_TEST)\n";
}

foreach ($profiles as $profile) {
    $step('profile '.$profile->name, static fn () => ProfileBuilder::ensure($profile));
}

// catalog reorganizations leave orphaned template/worker dirs behind; on CI
// they would otherwise ride along in the apps cache forever
$known = array_keys(ProfileCatalog::all());
$fs = new Filesystem();
foreach ([dirname(Paths::profileDir('x')) => '*', dirname(Paths::workerDir('x'), 2) => '*/*', dirname(Paths::baselineDir('x'), 2) => '*/*'] as $root => $pattern) {
    foreach (glob($root.'/'.$pattern) ?: [] as $dir) {
        if (is_dir($dir) && !in_array(basename($dir), $known, true)) {
            $step('prune stale '.substr($dir, strlen(Paths::tmpPath()) + 1), static fn () => $fs->remove($dir));
        }
    }
}

if ('--validate' === $mode) {
    $failures = 0;
    foreach ($profiles as $profile) {
        $appDir = $step('prepare worker app '.$profile->name, static fn () => AppPool::preparedAppDir($profile->name));

        $containerMtimeMax = static function (string $env) use ($appDir): int {
            $max = 0;
            foreach (glob($appDir.'/var/cache/'.$env.'/*.php') ?: [] as $file) {
                $max = max($max, filemtime($file));
            }

            return $max;
        };

        $beforeDev = $containerMtimeMax('dev');
        $beforeTest = $containerMtimeMax('test');

        $step('boot check (dev, no debug) '.$profile->name, static fn () => ProcessRunner::run(
            'php bin/console about --env=dev --no-debug',
            $appDir,
            timeout: 60,
        ));
        // the test env boots WITH debug: this is the boot that actually runs
        // the container freshness checks the resetters' mtime preservation
        // exists to satisfy — a dev/no-debug probe alone cannot catch a
        // stale-mtime regression
        $step('boot check (test, debug) '.$profile->name, static fn () => ProcessRunner::run(
            'php bin/console about --env=test',
            $appDir,
            timeout: 60,
        ));

        if ($containerMtimeMax('dev') !== $beforeDev) {
            fwrite(\STDERR, sprintf("FAIL: profile \"%s\" recompiled its dev container on a pristine boot — the warm-cache path is broken.\n", $profile->name));
            ++$failures;
        }
        if ($containerMtimeMax('test') !== $beforeTest) {
            fwrite(\STDERR, sprintf("FAIL: profile \"%s\" recompiled its test container on a pristine debug boot — mtimes are not being preserved through resets.\n", $profile->name));
            ++$failures;
        }
    }

    exit($failures > 0 ? 1 : 0);
}
