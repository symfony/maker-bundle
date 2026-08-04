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
 * Before/after filesystem snapshot around each maker invocation. This is the
 * artifact-discovery mechanism — deliberately NOT a regex over stdout (the old
 * one only matched 3–4 char extensions and would miss .js/.ts).
 */
final class ArtifactManifest
{
    private const EXCLUDED_TOP_LEVEL = ['var', 'vendor', 'node_modules', '.git'];

    /**
     * @return array<string, string> relative path => content hash
     */
    public static function snapshot(string $appDir): array
    {
        // prune excluded trees BEFORE descending: vendor/ alone holds ~7500
        // files per app, and two snapshots bracket every maker run
        $prefixLength = \strlen($appDir) + 1;
        $directories = new \RecursiveCallbackFilterIterator(
            new \RecursiveDirectoryIterator($appDir, \FilesystemIterator::SKIP_DOTS),
            static fn (\SplFileInfo $file): bool => !(
                $file->isDir()
                && \strlen($file->getPathname()) - \strlen($file->getFilename()) === $prefixLength
                && \in_array($file->getFilename(), self::EXCLUDED_TOP_LEVEL, true)
            ),
        );

        $entries = [];
        foreach (new \RecursiveIteratorIterator($directories) as $file) {
            if (!$file->isFile()) {
                continue;
            }

            $entries[str_replace('\\', '/', substr($file->getPathname(), $prefixLength))] = md5_file($file->getPathname());
        }

        return $entries;
    }

    /**
     * @param array<string, string> $before
     * @param array<string, string> $after
     *
     * @return array{created: list<string>, updated: list<string>}
     */
    public static function diff(array $before, array $after): array
    {
        $created = array_keys(array_diff_key($after, $before));
        $updated = [];
        foreach (array_intersect_key($after, $before) as $path => $hash) {
            if ($before[$path] !== $hash) {
                $updated[] = $path;
            }
        }

        sort($created);
        sort($updated);

        return ['created' => $created, 'updated' => $updated];
    }
}
