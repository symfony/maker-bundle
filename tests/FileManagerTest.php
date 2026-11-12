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

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\MakerBundle\Exception\RuntimeCommandException;
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
    #[DataProvider('getRelativizePathTests')]
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

    public static function getRelativizePathTests()
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
    #[DataProvider('getAbsolutePathTests')]
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

    public static function getAbsolutePathTests()
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
    #[DataProvider('getIsPathInVendorTests')]
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

    public static function getIsPathInVendorTests()
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
    #[DataProvider('getPathForTemplateTests')]
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

    public static function getPathForTemplateTests()
    {
        yield 'its_a_folder' => [
            '/home/project/',
            '/home/project/templates',
            'templates/template.html.twig',
        ];
    }

    public function testResolveMakerTemplatePath()
    {
        $rootDir = str_replace('\\', '/', __DIR__).'/tmp/template_override_'.bin2hex(random_bytes(8));
        $fs = new Filesystem();
        $fs->mkdir($rootDir.'/templates/bundles/MakerBundle/command');

        try {
            $fileManager = new FileManager($fs, $this->createMock(AutoloaderUtil::class), new MakerFileLinkFormatter(null), $rootDir, 'templates');

            $this->assertSame(\dirname(__DIR__).'/templates/command/Command.tpl.php', $fileManager->resolveMakerTemplatePath('command/Command.tpl.php'));

            $overridePath = $rootDir.'/templates/bundles/MakerBundle/command/Command.tpl.php';
            $fs->dumpFile($overridePath, 'override');

            $this->assertSame($overridePath, $fileManager->resolveMakerTemplatePath('command/Command.tpl.php'));

            $fileManagerWithoutTwig = new FileManager($fs, $this->createMock(AutoloaderUtil::class), new MakerFileLinkFormatter(null), $rootDir);
            $this->assertSame($overridePath, $fileManagerWithoutTwig->resolveMakerTemplatePath('command/Command.tpl.php'));
        } finally {
            $fs->remove($rootDir);
        }
    }

    public function testResolveBundleTemplatePath()
    {
        $rootDir = str_replace('\\', '/', __DIR__).'/tmp/bundle_template_override_'.bin2hex(random_bytes(8));
        $bundleDir = str_replace('\\', '/', __DIR__).'/tmp/fake_bundle_'.bin2hex(random_bytes(8));
        $fs = new Filesystem();
        $fs->mkdir($rootDir.'/templates/bundles/AcmeBundle/report');
        $fs->mkdir($bundleDir.'/templates/report');
        $fs->dumpFile($bundleDir.'/templates/report/Report.tpl.php', 'bundle template');

        try {
            $fileManager = new FileManager($fs, $this->createMock(AutoloaderUtil::class), new MakerFileLinkFormatter(null), $rootDir, 'templates', ['AcmeBundle' => ['path' => $bundleDir]]);

            $this->assertSame($bundleDir.'/templates/report/Report.tpl.php', $fileManager->resolveMakerTemplatePath('@AcmeBundle/report/Report.tpl.php'));

            $overridePath = $rootDir.'/templates/bundles/AcmeBundle/report/Report.tpl.php';
            $fs->dumpFile($overridePath, 'override');

            $this->assertSame($overridePath, $fileManager->resolveMakerTemplatePath('@AcmeBundle/report/Report.tpl.php'));
        } finally {
            $fs->remove($rootDir);
            $fs->remove($bundleDir);
        }
    }

    public function testResolveUnknownBundleTemplatePath()
    {
        $bundleDir = str_replace('\\', '/', __DIR__).'/tmp/unknown_bundle_'.bin2hex(random_bytes(8));
        $fs = new Filesystem();
        $fs->mkdir($bundleDir.'/templates');

        try {
            $fileManager = new FileManager($fs, $this->createMock(AutoloaderUtil::class), new MakerFileLinkFormatter(null), __DIR__.'/tmp', 'templates', ['AcmeBundle' => ['path' => $bundleDir]]);

            $this->expectException(RuntimeCommandException::class);
            $this->expectExceptionMessage('Cannot find maker template "@AcmeBundle/report/Report.tpl.php".');

            $fileManager->resolveMakerTemplatePath('@AcmeBundle/report/Report.tpl.php');
        } finally {
            $fs->remove($bundleDir);
        }
    }

    public function testWithMakerFileLinkFormatter()
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
            ->willReturnCallback(static function ($path, $line) {
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
