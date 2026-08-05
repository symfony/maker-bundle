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

use Composer\InstalledVersions;

/**
 * Single source of truth for every location the harness reads or writes.
 */
final class Paths
{
    private static ?string $rootPath = null;

    public static function rootPath(): string
    {
        return self::$rootPath ??= realpath(InstalledVersions::getRootPackage()['install_path']);
    }

    public static function tmpPath(): string
    {
        return self::rootPath().'/tests/tmp';
    }

    public static function bundleProjectionDir(): string
    {
        return self::tmpPath().'/bundle/current';
    }

    public static function skeletonDir(): string
    {
        return self::tmpPath().'/skeleton/'.self::symfonyVersionDirName();
    }

    public static function profileDir(string $profile): string
    {
        return self::tmpPath().'/profiles/'.self::symfonyVersionDirName().'/'.$profile;
    }

    public static function workerDir(string $profile): string
    {
        return self::tmpPath().'/workers/'.self::symfonyVersionDirName().'/'.self::workerToken().'/'.$profile;
    }

    public static function baselineDir(string $profile): string
    {
        return self::tmpPath().'/baselines/'.self::symfonyVersionDirName().'/'.self::workerToken().'/'.$profile;
    }

    public static function markersDir(): string
    {
        return self::tmpPath().'/markers';
    }

    public static function vendorDirtyMarker(string $profile): string
    {
        return self::markersDir().'/'.self::symfonyVersionDirName().'-'.self::workerToken().'-'.$profile.'.vendor-dirty';
    }

    public static function keptDir(string $testId): string
    {
        $sanitized = preg_replace('/[^A-Za-z0-9_.-]/', '_', $testId);

        return self::tmpPath().'/kept/'.substr($sanitized, 0, 80).'-'.substr(md5($testId), 0, 8);
    }

    public static function fixturesPath(): string
    {
        return self::rootPath().'/tests/fixtures';
    }

    public static function harnessResourcesPath(): string
    {
        return \dirname(__DIR__).'/resources';
    }

    /**
     * The SYMFONY_VERSION constraint requested for the generated apps
     * (e.g. "6.4.*", "^8"), or '' for the latest supported skeleton.
     */
    public static function symfonyVersionConstraint(): string
    {
        return $_SERVER['SYMFONY_VERSION'] ?? getenv('SYMFONY_VERSION') ?: '';
    }

    public static function symfonyVersionDirName(): string
    {
        $version = self::symfonyVersionConstraint();
        if ('' === $version) {
            return 'current';
        }

        return preg_replace('/[^A-Za-z0-9.]/', '_', $version);
    }

    /**
     * Paratest sets TEST_TOKEN per worker; serial runs use a single worker.
     */
    public static function workerToken(): string
    {
        return $_SERVER['TEST_TOKEN'] ?? getenv('TEST_TOKEN') ?: 'main';
    }
}
