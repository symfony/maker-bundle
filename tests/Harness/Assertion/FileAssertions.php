<?php

/*
 * This file is part of the Symfony MakerBundle package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\MakerBundle\Tests\Harness\Assertion;

use PHPUnit\Framework\Assert;

/**
 * The file-equality mechanism: EOL-normalized so fixture comparisons stop
 * depending on core.autocrlf, with PHPUnit's string diff on failure.
 */
final class FileAssertions
{
    public static function assertFileEqualsFile(string $expectedFile, string $actualFile, string $message = ''): void
    {
        Assert::assertFileExists($actualFile, $message);

        Assert::assertSame(
            self::normalize(file_get_contents($expectedFile)),
            self::normalize(file_get_contents($actualFile)),
            $message ?: \sprintf('File "%s" does not match the expected fixture "%s".', $actualFile, $expectedFile),
        );
    }

    public static function assertFileContains(string $file, string $needle): void
    {
        Assert::assertFileExists($file);
        Assert::assertStringContainsString($needle, file_get_contents($file), \sprintf('File "%s" does not contain "%s".', $file, $needle));
    }

    public static function assertFileNotContains(string $file, string $needle): void
    {
        Assert::assertFileExists($file);
        Assert::assertStringNotContainsString($needle, file_get_contents($file), \sprintf('File "%s" unexpectedly contains "%s".', $file, $needle));
    }

    private static function normalize(string $contents): string
    {
        return str_replace("\r\n", "\n", $contents);
    }
}
