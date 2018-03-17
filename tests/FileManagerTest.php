<?php

namespace Symfony\Bundle\MakerBundle\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\Bundle\MakerBundle\FileManager;
use Symfony\Bundle\MakerBundle\Util\AutoloaderUtil;
use Symfony\Component\Filesystem\Filesystem;

class FileManagerTest extends TestCase
{
    /**
     * @dataProvider getRelativizePathTests
     */
    public function testRelativizePath(string $rootDir, string $absolutePath, string $expectedPath)
    {
        $fileManager = new FileManager(new Filesystem(), $this->createMock(AutoloaderUtil::class), $rootDir);

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

        yield 'windows_path' => [
            'D:\path\to\project',
            'D:\path\to\project\vendor\composer/../../src/Controller/TestController.php',
            'src/Controller/TestController.php',
        ];
    }

    /**
     * @dataProvider getAbsolutePathTests
     */
    public function testAbsolutizePath(string $rootDir, string $path, string $expectedPath)
    {
        $fileManager = new FileManager(new Filesystem(), $this->createMock(AutoloaderUtil::class), $rootDir);
        $this->assertSame($expectedPath, $fileManager->absolutizePath($path));
    }

    public function getAbsolutePathTests()
    {
        yield 'normal_path_change' => [
            '/home/project/',
            'foo/bar',
            '/home/project/foo/bar',
        ];

        yield 'already_absolute_path' => [
            '/home/project/',
            '/foo/bar',
            '/foo/bar',
        ];

        yield 'windows_already_absolute_path' => [
            'D:\path\to\project',
            'D:\foo\bar',
            'D:\foo\bar',
        ];

        yield 'windows_already_absolute_path' => [
            'D:\path\to\project',
            'D:/foo/bar',
            'D:/foo/bar',
        ];
    }

    /**
     * @dataProvider getIsPathInVendorTests
     */
    public function testIsPathInVendor(string $rootDir, string $path, bool $expectedIsInVendor)
    {
        $fileManager = new FileManager(new Filesystem(), $this->createMock(AutoloaderUtil::class), $rootDir);
        $this->assertSame($expectedIsInVendor, $fileManager->isPathInVendor($path));
    }

    public function getIsPathInVendorTests()
    {
        yield 'not_in_vendor' => [
            '/home/project/',
            '/home/project/foo/bar',
            false,
        ];

        yield 'in_vendor' => [
            '/home/project/',
            '/home/project/vendor/foo',
            true,
        ];

        yield 'not_in_this_vendor' => [
            '/home/project/',
            '/other/path/vendor/foo',
            false,
        ];

        yield 'windows_not_in_vendor' => [
            'D:\path\to\project',
            'D:\path\to\project\src\foo',
            false,
        ];

        yield 'windows_in_vendor' => [
            'D:\path\to\project',
            'D:\path\to\project\vendor\foo',
            true,
        ];
    }
}
