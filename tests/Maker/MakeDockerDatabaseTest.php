<?php

/*
 * This file is part of the Symfony MakerBundle package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\MakerBundle\Tests\Maker;

use Symfony\Bundle\MakerBundle\Maker\MakeDockerDatabase;
use Symfony\Bundle\MakerBundle\Test\MakerTestCase;
use Symfony\Bundle\MakerBundle\Test\MakerTestDetails;
use Symfony\Bundle\MakerBundle\Util\ComposeFileManipulator;
use Symfony\Component\Filesystem\Filesystem;

/**
 * @author Jesse Rushlow <jr@rushlow.dev>
 */
final class MakeDockerDatabaseTest extends MakerTestCase
{
    public function getTestDetails(): \Generator
    {
        yield 'uses_3_7_compose_file_version' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeDockerDatabase::class),
            [
                '0', // Select MySQL as the database
                '', // use the default "latest" service version
            ]
        )
            ->assert(
                function (string $output, string $directory) {
                    $fs = new Filesystem();

                    $composeFile = sprintf('%s/%s', $directory, 'docker-compose.yaml');

                    self::assertTrue($fs->exists($composeFile));

                    $manipulator = new ComposeFileManipulator(file_get_contents($composeFile));

                    $data = $manipulator->getComposeData();

                    self::assertSame('3.7', $data['version']);
                }
            ),
        ];
        yield 'creates_mysql_service' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeDockerDatabase::class),
            [
                '0', // Select MySQL as the database
                '', // use the default "latest" service version
            ]
        )
            ->assert(
                function (string $output, string $directory) {
                    $this->assertStringContainsString('Success', $output);

                    $composeFile = sprintf('%s/%s', $directory, 'docker-compose.yaml');
                    $manipulator = new ComposeFileManipulator(file_get_contents($composeFile));

                    self::assertTrue($manipulator->serviceExists('database'));

                    $data = $manipulator->getComposeData();
                    $mysql = $data['services']['database'];

                    self::assertSame('mysql:latest', $mysql['image']);
                    self::assertSame('password', $mysql['environment']['MYSQL_ROOT_PASSWORD']);
                    self::assertSame('main', $mysql['environment']['MYSQL_DATABASE']);
                    self::assertSame(['3306'], $mysql['ports']);
                }
            ),
        ];

        yield 'creates_mariadb_service' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeDockerDatabase::class),
            [
                '1', // Select MariaDB as the database
                '', // use the default "latest" service version
            ]
        )
            ->assert(
                function (string $output, string $directory) {
                    $this->assertStringContainsString('Success', $output);

                    $composeFile = sprintf('%s/%s', $directory, 'docker-compose.yaml');
                    $manipulator = new ComposeFileManipulator(file_get_contents($composeFile));

                    self::assertTrue($manipulator->serviceExists('database'));

                    $data = $manipulator->getComposeData();
                    $mariadb = $data['services']['database'];

                    self::assertSame('mariadb:latest', $mariadb['image']);
                    self::assertSame('password', $mariadb['environment']['MYSQL_ROOT_PASSWORD']);
                    self::assertSame('main', $mariadb['environment']['MYSQL_DATABASE']);
                    self::assertSame(['3306'], $mariadb['ports']);
                }
            ),
        ];

        yield 'create_postgres_service' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeDockerDatabase::class),
            [
                '2', // Select Postgres as the database
                '', // use the default "alpine" service version
            ]
        )
            ->assert(
                function (string $output, string $directory) {
                    $this->assertStringContainsString('Success', $output);

                    $composeFile = sprintf('%s/%s', $directory, 'docker-compose.yaml');
                    $manipulator = new ComposeFileManipulator(file_get_contents($composeFile));

                    self::assertTrue($manipulator->serviceExists('database'));

                    $data = $manipulator->getComposeData();
                    $postgres = $data['services']['database'];

                    self::assertSame('postgres:alpine', $postgres['image']);
                    self::assertSame('main', $postgres['environment']['POSTGRES_USER']);
                    self::assertSame('main', $postgres['environment']['POSTGRES_PASSWORD']);
                    self::assertSame('main', $postgres['environment']['POSTGRES_DB']);
                    self::assertSame(['5432'], $postgres['ports']);
                }
            ),
        ];
    }
}
