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
use Symfony\Bundle\MakerBundle\Test\MakerTestRunner;
use Symfony\Bundle\MakerBundle\Util\ComposeFileManipulator;

/**
 * @author Jesse Rushlow <jr@rushlow.dev>
 */
final class MakeDockerDatabaseTest extends MakerTestCase
{
    protected function getMakerClass(): string
    {
        return MakeDockerDatabase::class;
    }

    public function getTestDetails(): \Generator
    {
        yield 'it_uses_3_7_compose_file_version_generates_mysql_database' => [$this->createMakerTest()
            ->run(function (MakerTestRunner $runner) {
                $runner->runMaker([
                    '0', // Select MySQL as the database
                    '', // use the default "latest" service version
                ]);

                $this->assertFileExists($runner->getPath('compose.yaml'));

                $manipulator = $this->getComposeManipulator($runner);
                $data = $manipulator->getComposeData();

                self::assertSame('3.7', $data['version']);

                self::assertTrue($manipulator->serviceExists('database'));

                $mysql = $data['services']['database'];

                self::assertSame('mysql:latest', $mysql['image']);
                self::assertSame('password', $mysql['environment']['MYSQL_ROOT_PASSWORD']);
                self::assertSame('main', $mysql['environment']['MYSQL_DATABASE']);
                self::assertSame(['3306'], $mysql['ports']);
            }),
        ];

        yield 'it_creates_mariadb' => [$this->createMakerTest()
            ->run(function (MakerTestRunner $runner) {
                $output = $runner->runMaker([
                    '1', // Select MariaDB as the database
                    '', // use the default "latest" service version
                ]);

                $this->assertStringContainsString('Success', $output);

                $manipulator = $this->getComposeManipulator($runner);

                self::assertTrue($manipulator->serviceExists('database'));

                $data = $manipulator->getComposeData();
                $mariadb = $data['services']['database'];

                self::assertSame('mariadb:latest', $mariadb['image']);
                self::assertSame('password', $mariadb['environment']['MYSQL_ROOT_PASSWORD']);
                self::assertSame('main', $mariadb['environment']['MYSQL_DATABASE']);
                self::assertSame(['3306'], $mariadb['ports']);
            }),
        ];

        yield 'it_creates_postgresql' => [$this->createMakerTest()
            ->run(function (MakerTestRunner $runner) {
                $output = $runner->runMaker([
                    '2', // Select Postgres as the database
                    '', // use the default "alpine" service version
                ]);

                $this->assertStringContainsString('Success', $output);

                $manipulator = $this->getComposeManipulator($runner);

                self::assertTrue($manipulator->serviceExists('database'));

                $data = $manipulator->getComposeData();
                $postgres = $data['services']['database'];

                self::assertSame('postgres:alpine', $postgres['image']);
                self::assertSame('main', $postgres['environment']['POSTGRES_USER']);
                self::assertSame('main', $postgres['environment']['POSTGRES_PASSWORD']);
                self::assertSame('main', $postgres['environment']['POSTGRES_DB']);
                self::assertSame(['5432'], $postgres['ports']);
            }),
        ];
    }

    private function getComposeManipulator(MakerTestRunner $runner): ComposeFileManipulator
    {
        $composeFile = $runner->getPath('compose.yaml');

        return new ComposeFileManipulator(file_get_contents($composeFile));
    }
}
