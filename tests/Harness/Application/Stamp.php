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

/**
 * Validity stamps embedded inside each built directory (skeleton, profile), so
 * a cache-restored directory always carries its own validity information.
 *
 * The ISO-week epoch is part of the fingerprint itself, not only of any CI
 * cache key: a directory restored from a previous epoch is stale by definition
 * and gets rebuilt, so the weekly refresh really re-resolves dependencies.
 */
final class Stamp
{
    public const FILENAME = '.maker-harness-stamp.json';

    public static function epoch(): string
    {
        return (new \DateTimeImmutable('now'))->format('o-\WW');
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function read(string $dir): ?array
    {
        $file = $dir.'/'.self::FILENAME;
        if (!is_file($file)) {
            return null;
        }

        $data = json_decode(file_get_contents($file), true);

        return \is_array($data) ? $data : null;
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function write(string $dir, array $data): void
    {
        file_put_contents($dir.'/'.self::FILENAME, json_encode($data + ['builtAt' => date('c')], \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES));
    }

    public static function isValid(string $dir, string $expectedFingerprint): bool
    {
        return ($stamp = self::read($dir)) && ($stamp['fingerprint'] ?? null) === $expectedFingerprint;
    }
}
