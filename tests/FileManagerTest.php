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
    public function testRelativizePath(string $rootDir, string $twigDefaultPath, string $absolutePath, string $expectedPath)
    {
        $fileManager = new FileManager(new Filesystem(), $this->createMock(AutoloaderUtil::class), $rootDir, $twigDefaultPath);

        $this->assertSame($expectedPath, $fileManager->relativizePath($absolutePath));
    }

    public function getRelativizePathTests()
    {
        yield [
            '/home/project',
            'templates',
            '/some/other/path',
            '/some/other/path',
        ];

        yield [
            '/home/project',
            'templates',
            '/home/project/foo/bar',
            'foo/bar',
        ];

        yield [
            '/home/project',
            'templates',
            '/home/project//foo/./bar',
            'foo/bar',
        ];

        yield 'relative_dot_path' => [
            '/home/project',
            'templates',
            '/home/project/foo/bar/../../src/Baz.php',
            'src/Baz.php',
        ];

        yield 'windows_path' => [
            'D:\path\to\project',
            'templates',
            'D:\path\to\project\vendor\composer/../../src/Controller/TestController.php',
            'src/Controller/TestController.php',
        ];

        yield 'double_src' => [
            '/src',
            'templates',
            '/src/vendor/composer/../../src/Command/FooCommand.php',
            'src/Command/FooCommand.php',
        ];
    }

    /**
     * @dataProvider getAbsolutePathTests
     */
    public function testAbsolutizePath(string $rootDir, string $twigDefaultPath, string $path, string $expectedPath)
    {
        $fileManager = new FileManager(new Filesystem(), $this->createMock(AutoloaderUtil::class), $rootDir, $twigDefaultPath);
        $this->assertSame($expectedPath, $fileManager->absolutizePath($path));
    }

    public function getAbsolutePathTests()
    {
        yield 'normal_path_change' => [
            '/home/project/',
            'templates',
            'foo/bar',
            '/home/project/foo/bar',
        ];

        yield 'already_absolute_path' => [
            '/home/project/',
            'templates',
            '/foo/bar',
            '/foo/bar',
        ];

        yield 'windows_already_absolute_path' => [
            'D:\path\to\project',
            'templates',
            'D:\foo\bar',
            'D:\foo\bar',
        ];

        yield 'windows_already_absolute_path' => [
            'D:\path\to\project',
            'templates',
            'D:/foo/bar',
            'D:/foo/bar',
        ];
    }

    /**
     * @dataProvider getIsPathInVendorTests
     */
    public function testIsPathInVendor(string $rootDir, string $twigDefaultPath, string $path, bool $expectedIsInVendor)
    {
        $fileManager = new FileManager(new Filesystem(), $this->createMock(AutoloaderUtil::class), $rootDir, $twigDefaultPath);
        $this->assertSame($expectedIsInVendor, $fileManager->isPathInVendor($path));
    }

    public function getIsPathInVendorTests()
    {
        yield 'not_in_vendor' => [
            '/home/project/',
            'templates',
            '/home/project/foo/bar',
            false,
        ];

        yield 'in_vendor' => [
            '/home/project/',
            'templates',
            '/home/project/vendor/foo',
            true,
        ];

        yield 'not_in_this_vendor' => [
            '/home/project/',
            'templates',
            '/other/path/vendor/foo',
            false,
        ];

        yield 'windows_not_in_vendor' => [
            'D:\path\to\project',
            'templates',
            'D:\path\to\project\src\foo',
            false,
        ];

        yield 'windows_in_vendor' => [
            'D:\path\to\project',
            'templates',
            'D:\path\to\project\vendor\foo',
            true,
        ];
    }

    /**
     * @dataProvider getTestTemplatesFolder
     */
    public function testTemplatesFolder(string $rootDir, string $twigDefaultPath, string $expectedTemplatesFolder)
    {
        $fileManager = new FileManager(new Filesystem(), $this->createMock(AutoloaderUtil::class), $rootDir, $twigDefaultPath);
        $this->assertSame($expectedTemplatesFolder, $fileManager->getTemplatesDir());
    }

    public function getTestTemplatesFolder()
    {
        yield 'its_a_folder' => [
            '/home/project/',
            'templates',
            'templates/',
        ];
    }
}
