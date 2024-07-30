<?php

/*
 * This file is part of the Symfony MakerBundle package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\MakerBundle\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\Bundle\MakerBundle\FileManager;
use Symfony\Bundle\MakerBundle\Util\AutoloaderUtil;
use Symfony\Bundle\MakerBundle\Util\MakerFileLinkFormatter;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\ErrorHandler\ErrorRenderer\FileLinkFormatter;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\Debug\FileLinkFormatter as LegacyFileLinkFormatter;

class FileManagerTest extends TestCase
{
    /**
     * @dataProvider getRelativizePathTests
     */
    public function testRelativizePath(string $rootDir, string $absolutePath, string $expectedPath)
    {
        $fileManager = new FileManager(
            new Filesystem(),
            $this->createMock(AutoloaderUtil::class),
            new MakerFileLinkFormatter(null),
            $rootDir
        );

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

        yield 'double_src' => [
            '/src',
            '/src/vendor/composer/../../src/Command/FooCommand.php',
            'src/Command/FooCommand.php',
        ];
    }

    /**
     * @dataProvider getAbsolutePathTests
     */
    public function testAbsolutizePath(string $rootDir, string $path, string $expectedPath)
    {
        $fileManager = new FileManager(
            new Filesystem(),
            $this->createMock(AutoloaderUtil::class),
            new MakerFileLinkFormatter(null),
            $rootDir
        );
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

        yield 'windows_already_absolute_path_bis' => [
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
        $fileManager = new FileManager(
            new Filesystem(),
            $this->createMock(AutoloaderUtil::class),
            new MakerFileLinkFormatter(null),
            $rootDir
        );
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

    /**
     * @dataProvider getPathForTemplateTests
     */
    public function testPathForTemplate(string $rootDir, string $twigDefaultPath, string $expectedTemplatesFolder)
    {
        $fileManager = new FileManager(
            new Filesystem(),
            $this->createMock(AutoloaderUtil::class),
            new MakerFileLinkFormatter(null),
            $rootDir,
            $twigDefaultPath
        );
        $this->assertSame($expectedTemplatesFolder, $fileManager->getPathForTemplate('template.html.twig'));
    }

    public function getPathForTemplateTests()
    {
        yield 'its_a_folder' => [
            '/home/project/',
            '/home/project/templates',
            'templates/template.html.twig',
        ];
    }

    public function testWithMakerFileLinkFormatter(): void
    {
        if (getenv('MAKER_DISABLE_FILE_LINKS')) {
            $this->markTestSkipped();
        }

        if (class_exists(FileLinkFormatter::class)) {
            $fileLinkFormatter = $this->createMock(FileLinkFormatter::class);
        } else {
            $fileLinkFormatter = $this->createMock(LegacyFileLinkFormatter::class);
        }

        $fileLinkFormatter
            ->method('format')
            ->willReturnCallback(function ($path, $line) {
                return \sprintf('subl://open?url=file://%s&line=%d', $path, $line);
            });

        $fileManager = new FileManager(
            $this->createMock(Filesystem::class),
            $this->createMock(AutoloaderUtil::class),
            new MakerFileLinkFormatter($fileLinkFormatter),
            '/app'
        );

        $io = $this->createMock(SymfonyStyle::class);
        $io
            ->expects($this->once())
            ->method('comment')
            ->with("<fg=green>no change</>: \033]8;;subl://open?url=file:///app/myfile&line=1\033\\myfile\033]8;;\033\\");

        $fileManager->setIO($io);

        $fileManager->dumpFile('myfile', '');
    }
}
