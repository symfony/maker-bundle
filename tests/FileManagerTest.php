<?php

namespace Symfony\Bundle\MakerBundle\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\Bundle\MakerBundle\FileManager;
use Symfony\Component\Filesystem\Filesystem;

class FileManagerTest extends TestCase
{
    /**
     * @dataProvider getRelativizePathTests
     */
    public function testRelativizePath(string $rootDir, string $absolutePath, string $expectedPath)
    {
        $fileManager = new FileManager(new Filesystem(), $rootDir);

        $this->assertSame($expectedPath, $fileManager->relativizePath($absolutePath));
    }

    public function getRelativizePathTests()
    {
        yield [
            '/home/project',
            '/some/other/path',
            '/some/other/path',
        ];

        yield [
            '/home/project',
            '/home/project/foo/bar',
            'foo/bar',
        ];

        yield [
            '/home/project',
            '/home/project//foo/./bar',
            'foo/bar',
        ];

        yield 'relative_dot_path' => [
            '/home/project',
            '/home/project/foo/bar/../../src/Baz.php',
            'src/Baz.php',
        ];
    }
}
