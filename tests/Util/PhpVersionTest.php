<?php

/*
 * This file is part of the Symfony MakerBundle package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\MakerBundle\Tests\Util;

use PHPUnit\Framework\TestCase;
use Symfony\Bundle\MakerBundle\FileManager;
use Symfony\Bundle\MakerBundle\Util\PhpCompatUtil;
use Symfony\Component\HttpKernel\Kernel;

/**
 * @author Jesse Rushlow <jr@rushlow.dev>
 */
class PhpVersionTest extends TestCase
{
    /**
     * @dataProvider phpVersionDataProvider
     */
    public function testUsesPhpPlatformFromComposerJsonFileForCanUseAttributes(string $version, bool $expectedResult): void
    {
        $json = sprintf('{"platform-overrides": {"php": "%s"}}', $version);

        $mockFileManager = $this->createMock(FileManager::class);
        $mockFileManager
            ->expects(self::once())
            ->method('getRootDirectory')
            ->willReturn('/test')
        ;

        $mockFileManager
            ->expects(self::once())
            ->method('fileExists')
            ->with('/test/composer.lock')
            ->willReturn(true)
        ;

        $mockFileManager
            ->expects(self::once())
            ->method('getFileContents')
            ->with('/test/composer.lock')
            ->willReturn($json)
        ;

        $version = new PhpCompatUtil($mockFileManager);

        $result = $version->canUseAttributes();

        /*
         * Symfony 5.2 is required to compare the result. Otherwise it will always
         * return false regardless of the PHP Version. If the test suite is run w/
         * Symfony < 5.2, we assert false here but still rely on the assertions above.
         */
        if (Kernel::VERSION_ID < 50200) {
            $expectedResult = false;
        }

        self::assertSame($expectedResult, $result);
    }

    public function phpVersionDataProvider(): \Generator
    {
        yield ['8', true];
        yield ['8.0', true];
        yield ['8.0.1', true];
        yield ['8RC1', true];
        yield ['8.1alpha1', true];
        yield ['8.0.0beta2', true];
        yield ['8beta', true];
        yield ['7', false];
        yield ['7.0', false];
        yield ['7.1.1', false];
        yield ['7.0.0RC3', false];
    }

    public function testFallBackToPhpVersionWithoutLockFile(): void
    {
        $mockFileManager = $this->createMock(FileManager::class);
        $mockFileManager
            ->expects(self::once())
            ->method('getRootDirectory')
            ->willReturn('/test')
        ;

        $mockFileManager
            ->expects(self::once())
            ->method('fileExists')
            ->with('/test/composer.lock')
            ->willReturn(false)
        ;

        $mockFileManager
            ->expects(self::never())
            ->method('getFileContents')
        ;

        $util = new PhpCompatUtilTestFixture($mockFileManager);

        $result = $util->getVersionForTest();

        self::assertSame(\PHP_VERSION, $result);
    }

    public function testWithoutPlatformVersionSet(): void
    {
        $json = '{"platform-overrides": {}}';

        $mockFileManager = $this->createMock(FileManager::class);
        $mockFileManager
            ->expects(self::once())
            ->method('getRootDirectory')
            ->willReturn('/test')
        ;

        $mockFileManager
            ->expects(self::once())
            ->method('fileExists')
            ->with('/test/composer.lock')
            ->willReturn(true)
        ;

        $mockFileManager
            ->expects(self::once())
            ->method('getFileContents')
            ->with('/test/composer.lock')
            ->willReturn($json)
        ;

        $util = new PhpCompatUtilTestFixture($mockFileManager);

        $result = $util->getVersionForTest();

        self::assertSame(\PHP_VERSION, $result);
    }

    /**
     * @dataProvider phpVersionForTypedPropertiesDataProvider
     */
    public function testCanUseTypedProperties(string $version, bool $expectedResult): void
    {
        $json = sprintf('{"platform-overrides": {"php": "%s"}}', $version);

        $mockFileManager = $this->createMock(FileManager::class);
        $mockFileManager
            ->expects(self::once())
            ->method('getRootDirectory')
            ->willReturn('/test')
        ;

        $mockFileManager
            ->expects(self::once())
            ->method('fileExists')
            ->with('/test/composer.lock')
            ->willReturn(true)
        ;

        $mockFileManager
            ->expects(self::once())
            ->method('getFileContents')
            ->with('/test/composer.lock')
            ->willReturn($json)
        ;

        $version = new PhpCompatUtil($mockFileManager);

        $result = $version->canUseTypedProperties();

        self::assertSame($expectedResult, $result);
    }

    public function phpVersionForTypedPropertiesDataProvider(): \Generator
    {
        yield ['8', true];
        yield ['8.0.1', true];
        yield ['8RC1', true];
        yield ['7.4', true];
        yield ['7.4.6', true];
        yield ['7', false];
        yield ['7.0', false];
        yield ['5.7', false];
    }

    /**
     * @dataProvider phpVersionForObjectTypehintDataProvider
     */
    public function testCanUseObjectTypehint(string $version, bool $expectedResult): void
    {
        $json = sprintf('{"platform-overrides": {"php": "%s"}}', $version);

        $mockFileManager = $this->createMock(FileManager::class);
        $mockFileManager
            ->expects(self::once())
            ->method('getRootDirectory')
            ->willReturn('/test')
        ;

        $mockFileManager
            ->expects(self::once())
            ->method('fileExists')
            ->with('/test/composer.lock')
            ->willReturn(true)
        ;

        $mockFileManager
            ->expects(self::once())
            ->method('getFileContents')
            ->with('/test/composer.lock')
            ->willReturn($json)
        ;

        $version = new PhpCompatUtil($mockFileManager);

        $result = $version->canUseObjectTypehint();

        self::assertSame($expectedResult, $result);
    }

    public function phpVersionForObjectTypehintDataProvider(): \Generator
    {
        yield ['8', true];
        yield ['8.0.1', true];
        yield ['8RC1', true];
        yield ['7.4', true];
        yield ['7.4.6', true];
        yield ['7.2.1', true];
        yield ['7.2', true];
        yield ['7.1.5', false];
        yield ['7', false];
        yield ['5.7', false];
    }
}

class PhpCompatUtilTestFixture extends PhpCompatUtil
{
    public function getVersionForTest(): string
    {
        return $this->getPhpVersion();
    }
}
