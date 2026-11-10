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
use Symfony\Bundle\MakerBundle\FileManager;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\Util\AutoloaderUtil;
use Symfony\Bundle\MakerBundle\Util\MakerFileLinkFormatter;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

class GeneratorTest extends TestCase
{
    /**
     * @dataProvider getClassNameDetailsTests
     */
    #[DataProvider('getClassNameDetailsTests')]
    public function testCreateClassNameDetails(string $name, string $prefix, string $suffix, string $expectedFullClassName, string $expectedRelativeClassName)
    {
        $fileManager = $this->createMock(FileManager::class);
        $fileManager->expects($this->any())
            ->method('getNamespacePrefixForClass')
            ->willReturn('Foo');

        $generator = new Generator($fileManager, 'App\\');

        $classNameDetails = $generator->createClassNameDetails($name, $prefix, $suffix);

        $this->assertSame($expectedFullClassName, $classNameDetails->getFullName());
        $this->assertSame($expectedRelativeClassName, $classNameDetails->getRelativeName());
    }

    public function testClassOfADependencyIsPrefixedRatherThanReused(): void
    {
        $fileManager = $this->createMock(FileManager::class);
        $fileManager->expects($this->any())
            ->method('getNamespacePrefixForClass')
            ->willReturn('Foo');
        $fileManager->expects($this->once())
            ->method('isPathInVendor')
            ->willReturn(true);

        $generator = new Generator($fileManager, 'App\\');

        $classNameDetails = $generator->createClassNameDetails(Finder::class, 'Entity');

        $this->assertSame('App\\Entity\\Symfony\\Component\\Finder\\Finder', $classNameDetails->getFullName());
    }

    public function testTemplateOverrideIsUsed()
    {
        $rootDir = __DIR__.'/tmp/generator_template_override_'.bin2hex(random_bytes(8));
        $fs = new Filesystem();
        $fs->mkdir($rootDir.'/templates/bundles/MakerBundle/command');
        $fs->dumpFile($rootDir.'/templates/bundles/MakerBundle/command/Command.tpl.php', '<?= $class_name; ?> from the override');

        try {
            $autoloaderUtil = $this->createMock(AutoloaderUtil::class);
            $autoloaderUtil->method('getPathForFutureClass')->willReturn($rootDir.'/src/Command/FooCommand.php');

            $fileManager = new FileManager($fs, $autoloaderUtil, new MakerFileLinkFormatter(null), $rootDir, 'templates');
            $generator = new Generator($fileManager, 'App\\');

            $targetPath = $generator->generateClass('App\\Command\\FooCommand', 'command/Command.tpl.php');

            $this->assertSame('FooCommand from the override', $generator->getFileContentsForPendingOperation($targetPath));
        } finally {
            $fs->remove($rootDir);
        }
    }

    public function testBundleTemplateReferenceIsUsed()
    {
        $rootDir = __DIR__.'/tmp/generator_bundle_template_'.bin2hex(random_bytes(8));
        $bundleDir = __DIR__.'/tmp/generator_fake_bundle_'.bin2hex(random_bytes(8));
        $fs = new Filesystem();
        $fs->mkdir($bundleDir.'/templates/report');
        $fs->dumpFile($bundleDir.'/templates/report/Report.tpl.php', '<?= $class_name; ?> from the bundle');

        try {
            $autoloaderUtil = $this->createMock(AutoloaderUtil::class);
            $autoloaderUtil->method('getPathForFutureClass')->willReturn($rootDir.'/src/Report/FooReport.php');

            $fileManager = new FileManager($fs, $autoloaderUtil, new MakerFileLinkFormatter(null), $rootDir, 'templates', ['AcmeBundle' => ['path' => $bundleDir]]);
            $generator = new Generator($fileManager, 'App\\');

            $targetPath = $generator->generateClass('App\\Report\\FooReport', '@AcmeBundle/report/Report.tpl.php');

            $this->assertSame('FooReport from the bundle', $generator->getFileContentsForPendingOperation($targetPath));
        } finally {
            $fs->remove($rootDir);
            $fs->remove($bundleDir);
        }
    }

    public static function getClassNameDetailsTests(): \Generator
    {
        yield 'simple_class' => [
            'foo',
            'Controller\\',
            '',
            'App\\Controller\\Foo',
            'Foo',
        ];

        yield 'with_suffix' => [
            'foo',
            'Controller',
            'Controller',
            'App\\Controller\\FooController',
            'FooController',
        ];

        yield 'custom_class' => [
            '\Foo\Bar\Baz',
            'Controller',
            '',
            'Foo\Bar\Baz',
            'Bar\Baz',
        ];

        yield 'custom_class_with_suffix' => [
            '\Foo\Bar\Baz',
            'Controller',
            'Controller',
            'Foo\Bar\Baz',
            'Bar\Baz',
        ];

        yield 'enty_fqcn' => [
            '\\App\\Entity\\User',
            'Entity\\',
            '',
            'App\\Entity\\User',
            'User',
        ];

        // "Directory" is a class of the PHP core, so the bare name must still be prefixed
        yield 'bare_name_of_an_existing_class' => [
            'Directory',
            'Entity',
            '',
            'App\\Entity\\Directory',
            'Directory',
        ];

        yield 'non_prefixed_fake_fqcn' => [
            'App\\Entity\\User',
            '',
            '',
            'App\\App\\Entity\\User',
            'Entity\\User',
        ];

        yield 'real_fqcn_with_suffix' => [
            'Symfony\\Bundle\\MakerBundle\\Tests\\Generator',
            'Test',
            'Test',
            'Symfony\\Bundle\\MakerBundle\\Tests\\GeneratorTest',
            'Symfony\\Bundle\\MakerBundle\\Tests\\GeneratorTest',
        ];

        yield 'real_fqcn_without_suffix' => [
            'Symfony\\Bundle\\MakerBundle\\Tests\\GeneratorTest',
            '',
            '',
            'Symfony\\Bundle\\MakerBundle\\Tests\\GeneratorTest',
            'Symfony\\Bundle\\MakerBundle\\Tests\\GeneratorTest',
        ];
    }
}
