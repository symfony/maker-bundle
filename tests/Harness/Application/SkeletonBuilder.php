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

use Symfony\Bundle\MakerBundle\Tests\Harness\Process\ProcessRunner;

/**
 * One `composer create-project symfony/skeleton` per SYMFONY_VERSION, with the
 * MakerBundle installed from a Composer path repository pointing at the bundle
 * projection, so local edits apply live, without committing anything.
 */
final class SkeletonBuilder
{
    public static function ensure(): string
    {
        $dir = Paths::skeletonDir();
        $fingerprint = self::fingerprint();

        BundleProjection::repairVendorLink($dir);

        if (!Stamp::isValid($dir, $fingerprint)) {
            BuildLock::buildAtomically($dir, $fingerprint, self::build(...));
        }

        return $fingerprint;
    }

    public static function fingerprint(): string
    {
        return md5(json_encode([
            'symfonyVersion' => Paths::symfonyVersionConstraint(),
            'epoch' => Stamp::epoch(),
            'bundleComposerJson' => BundleProjection::dependencyInputHash(),
            // the build recipe itself: editing this file must invalidate
            // cached skeletons (and, via the parent fingerprint, profiles)
            'builder' => md5_file(__FILE__),
        ]));
    }

    private static function build(string $buildDir): void
    {
        BundleProjection::ensure();

        $version = Paths::symfonyVersionConstraint();
        $package = 'symfony/skeleton'.('' !== $version ? ':'.$version : '');

        ProcessRunner::run(
            ['composer', 'create-project', $package, $buildDir, '--prefer-dist', '--no-progress', '--no-interaction'],
            \dirname($buildDir),
            timeout: 600,
        );

        $pathRepository = json_encode([
            'type' => 'path',
            'url' => Paths::bundleProjectionDir(),
            'options' => [
                // arbitrary high version to avoid stability conflicts
                'versions' => ['symfony/maker-bundle' => '9999.99'],
            ],
        ]);
        ProcessRunner::run(['composer', 'config', 'repositories.maker-bundle', $pathRepository], $buildDir);

        ProcessRunner::run(
            ['composer', 'require', '--dev', 'symfony/maker-bundle', '--no-progress', '--no-interaction'],
            $buildDir,
            timeout: 600,
        );

        Stamp::write($buildDir, ['type' => 'skeleton', 'fingerprint' => self::fingerprint()]);
    }
}
