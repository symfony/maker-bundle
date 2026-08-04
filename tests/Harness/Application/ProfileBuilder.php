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

/**
 * Builds pristine profile templates: sources + vendor + embedded stamp.
 * Templates are NEVER warmed: compiled containers bake absolute paths, so
 * warming happens per worker, at the app's final path (see AppPool).
 */
final class ProfileBuilder
{
    /**
     * @return string the profile fingerprint
     */
    public static function ensure(Profile $profile): string
    {
        $parentFingerprint = null !== $profile->extends
            ? self::ensure(ProfileCatalog::get($profile->extends))
            : SkeletonBuilder::ensure();

        $fingerprint = self::fingerprint($profile, $parentFingerprint);
        $dir = Paths::profileDir($profile->name);

        BundleProjection::repairVendorLink($dir);

        if (!Stamp::isValid($dir, $fingerprint)) {
            BuildLock::buildAtomically($dir, $fingerprint, static function (string $buildDir) use ($profile, $fingerprint) {
                self::build($profile, $buildDir, $fingerprint);
            });
        }

        return $fingerprint;
    }

    public static function fingerprint(Profile $profile, string $parentFingerprint): string
    {
        return md5(json_encode([
            'definition' => $profile->definition(),
            'parent' => $parentFingerprint,
            // covers every profile definition, including future closures,
            // and the build logic itself
            'catalog' => md5_file(\dirname(__DIR__).'/Application/ProfileCatalog.php'),
            'builder' => md5_file(__FILE__),
            'bundleComposerJson' => BundleProjection::dependencyInputHash(),
            'epoch' => Stamp::epoch(),
        ]));
    }

    private static function build(Profile $profile, string $buildDir, string $fingerprint): void
    {
        $sourceDir = null !== $profile->extends ? Paths::profileDir($profile->extends) : Paths::skeletonDir();

        ResetterFactory::best()->syncTree($sourceDir, $buildDir, ['/'.Stamp::FILENAME]);
        // a healthy source link can still degrade THROUGH the copy (Windows
        // robocopy and cache-restored reparse points do not compose reliably),
        // so the invariant is re-established on the freshly synced tree
        BundleProjection::repairVendorLink($buildDir);

        if ('App' !== $profile->rootNamespace) {
            self::rewriteRootNamespace($buildDir, $profile->rootNamespace);
        }

        self::disableAdvisoryBlocking($buildDir);

        if ($requirements = $profile->composerRequirements(dev: false)) {
            ProcessRunner::run(
                array_merge(['composer', 'require'], $requirements, ['--no-progress', '--no-interaction']),
                $buildDir,
                timeout: 600,
            );
        }
        if ($devRequirements = $profile->composerRequirements(dev: true)) {
            ProcessRunner::run(
                array_merge(['composer', 'require', '--dev'], $devRequirements, ['--no-progress', '--no-interaction']),
                $buildDir,
                timeout: 600,
            );
        }

        foreach ($profile->patches as $patch) {
            self::applyPatch($buildDir, $patch);
        }

        $fs = new \Symfony\Component\Filesystem\Filesystem();
        foreach ($profile->files as $target => $source) {
            $fs->copy($source, $buildDir.'/'.$target, true);
        }

        foreach ($profile->postBuild as $command) {
            ProcessRunner::run($command, $buildDir, timeout: 600);
        }

        Stamp::write($buildDir, ['type' => 'profile', 'profile' => $profile->name, 'fingerprint' => $fingerprint]);
    }

    /**
     * Composer >= 2.9 refuses to RESOLVE package versions with open security
     * advisories, even under COMPOSER_NO_AUDIT (which only mutes the report).
     * That blocks api-platform/core ^3.4 (the only line the PHP 8.1 leg can
     * install) and is a production concern, not one for throwaway test apps.
     * `composer config` rejects policy.* keys, so the config block is patched
     * by string surgery (never a JSON round-trip: it reformats the file).
     */
    private static function disableAdvisoryBlocking(string $dir): void
    {
        $path = $dir.'/composer.json';
        $contents = file_get_contents($path);

        if (str_contains($contents, '"policy"')) {
            return;
        }

        $patched = str_replace('"config": {', "\"config\": {\n        \"policy\": { \"advisories\": { \"block\": false } },", $contents, $count);
        if (1 !== $count) {
            throw new \RuntimeException(\sprintf('Expected exactly one "config" block in "%s" to disable Composer advisory blocking, found %d.', $path, $count));
        }

        file_put_contents($path, $patched);
    }

    /**
     * @param array{file: string, find: string, replace: string, allowMissing?: bool} $patch
     */
    private static function applyPatch(string $dir, array $patch): void
    {
        $path = $dir.'/'.$patch['file'];
        $allowMissing = $patch['allowMissing'] ?? false;

        if (!is_file($path)) {
            if ($allowMissing) {
                return;
            }

            throw new \RuntimeException(\sprintf('Profile patch target "%s" not found.', $patch['file']));
        }

        $contents = file_get_contents($path);
        if (!str_contains($contents, $patch['find'])) {
            if ($allowMissing) {
                return;
            }

            throw new \RuntimeException(\sprintf('Profile patch could not find "%s" inside "%s".', $patch['find'], $patch['file']));
        }

        file_put_contents($path, str_replace($patch['find'], $patch['replace'], $contents));
    }

    private static function rewriteRootNamespace(string $dir, string $rootNamespace): void
    {
        $replacements = [
            ['file' => 'composer.json', 'find' => '"App\\\\": "src/"', 'replace' => '"'.$rootNamespace.'\\\\": "src/"'],
            ['file' => 'bin/console', 'find' => 'use App\\Kernel', 'replace' => 'use '.$rootNamespace.'\\Kernel'],
            ['file' => 'public/index.php', 'find' => 'use App\\Kernel', 'replace' => 'use '.$rootNamespace.'\\Kernel'],
            ['file' => 'config/services.yaml', 'find' => 'App\\', 'replace' => $rootNamespace.'\\'],
            ['file' => '.env.test', 'find' => "KERNEL_CLASS='App\Kernel'", 'replace' => "KERNEL_CLASS='".$rootNamespace."\Kernel'", 'allowMissing' => true],
            ['file' => 'config/packages/doctrine.yaml', 'find' => 'App', 'replace' => $rootNamespace, 'allowMissing' => true],
            // pre-7.3 skeletons scope attribute routing to the App\Controller
            // namespace; newer ones use `resource: routing.controllers`, which
            // is namespace-agnostic (controllers route via their service
            // registration) and has nothing to patch — hence allowMissing
            ['file' => 'config/routes.yaml', 'find' => 'namespace: App\Controller', 'replace' => 'namespace: '.$rootNamespace.'\Controller', 'allowMissing' => true],
        ];

        // what a real user sets when moving off the App\ namespace; the tests
        // on these profiles used to write it themselves, three times over
        (new \Symfony\Component\Filesystem\Filesystem())->mkdir($dir.'/config/packages/dev');
        file_put_contents(
            $dir.'/config/packages/dev/maker.yaml',
            "maker:\n    root_namespace: ".$rootNamespace."\n",
        );

        foreach ($replacements as $replacement) {
            self::applyPatch($dir, $replacement);
        }

        // Recipes may have added any number of classes under src/
        // (e.g. symfony/scheduler's src/Schedule.php), so the whole tree moves
        // to the new namespace, not just Kernel.php.
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir.'/src', \FilesystemIterator::SKIP_DOTS));
        foreach ($iterator as $file) {
            if (!$file->isFile() || 'php' !== $file->getExtension()) {
                continue;
            }
            $contents = file_get_contents($file->getPathname());
            $contents = preg_replace('/^namespace App(\\\\|;)/m', 'namespace '.$rootNamespace.'$1', $contents);
            $contents = preg_replace('/^use App\\\\/m', 'use '.$rootNamespace.'\\', $contents);
            file_put_contents($file->getPathname(), $contents);
        }

        ProcessRunner::run(['composer', 'dump-autoload', '--no-interaction'], $dir, timeout: 300);
    }
}
