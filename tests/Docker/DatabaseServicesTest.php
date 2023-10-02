<?php

/*
 * This file is part of the Symfony MakerBundle package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\MakerBundle\Tests\Docker;

use PHPUnit\Framework\TestCase;
use Symfony\Bundle\MakerBundle\Docker\DockerDatabaseServices;
use Symfony\Bundle\MakerBundle\Exception\RuntimeCommandException;

/**
 * @author Jesse Rushlow <jr@rushlow.dev>
 */
final class DatabaseServicesTest extends TestCase
{
    public function testExceptionThrownWithInvalidDatabaseProvided(): void
    {
        $this->expectException(RuntimeCommandException::class);
        $this->expectExceptionMessage('foo is not a valid / supported docker database type.');

        DockerDatabaseServices::getDatabaseSkeleton('foo', 'latest');
    }

    public function mixedNameDataProvider(): \Generator
    {
        yield ['mariadb'];
        yield ['mysql'];
        yield ['postgres'];
    }

    /**
     * @dataProvider mixedNameDataProvider
     */
    public function testSkeletonReturnArrayForDesiredDatabase(string $databaseName): void
    {
        $result = DockerDatabaseServices::getDatabaseSkeleton($databaseName, 'latest');

        self::assertArrayHasKey('image', $result);
        self::assertStringContainsStringIgnoringCase($databaseName, $result['image']);
    }

    /**
     * @dataProvider mixedNameDataProvider
     */
    public function testGetDefaultPorts(string $databaseName): void
    {
        $result = DockerDatabaseServices::getDefaultPorts($databaseName);

        ('postgres' === strtolower($databaseName)) ? self::assertSame(['5432'], $result) : self::assertSame(['3306'], $result);
    }

    /**
     * @dataProvider mixedNameDataProvider
     */
    public function testSuggestedVersion(string $databaseName): void
    {
        $result = DockerDatabaseServices::getSuggestedServiceVersion($databaseName);

        ('postgres' === strtolower($databaseName)) ? self::assertSame('alpine', $result) : self::assertSame('latest', $result);
    }
}
