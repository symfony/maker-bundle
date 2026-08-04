<?php

/*
 * This file is part of the Symfony MakerBundle package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\MakerBundle\Tests\Functional;

use Symfony\Bundle\MakerBundle\Maker\MakeDockerDatabase;
use Symfony\Bundle\MakerBundle\Tests\Harness\Application\Profiles;
use Symfony\Bundle\MakerBundle\Tests\Harness\Attribute\LegacyCase;
use Symfony\Bundle\MakerBundle\Tests\Harness\Attribute\MakerTest;
use Symfony\Bundle\MakerBundle\Tests\Harness\MakerTestCase;
use Symfony\Bundle\MakerBundle\Util\ComposeFileManipulator;

#[MakerTest(maker: MakeDockerDatabase::class, profile: Profiles::BASE)]
final class MakeDockerDatabaseTest extends MakerTestCase
{
    #[LegacyCase('MakeDockerDatabaseTest::it_uses_3_7_compose_file_version_generates_mysql_database')]
    public function testItGeneratesMysqlDatabase()
    {
        $this->app->runMaker()
            ->answer('Which database service will you be creating?', '0')
            ->acceptDefault('What version would you like to use?')
            ->run()
            ->assertCreated('compose.yaml');

        $data = $this->getComposeData();
        self::assertSame('3.7', $data['version']);

        $mysql = $data['services']['database'];
        self::assertSame('mysql:latest', $mysql['image']);
        self::assertSame('password', $mysql['environment']['MYSQL_ROOT_PASSWORD']);
        self::assertSame('main', $mysql['environment']['MYSQL_DATABASE']);
        self::assertSame(['3306'], $mysql['ports']);

        $this->app->assertComposeFileIsValid();
    }

    #[LegacyCase('MakeDockerDatabaseTest::it_creates_mariadb')]
    public function testItCreatesMariadb()
    {
        $this->app->runMaker()
            ->answer('Which database service will you be creating?', '1')
            ->acceptDefault('What version would you like to use?')
            ->run()
            ->assertOutputContains('Success')
            ->assertCreated('compose.yaml');

        $mariadb = $this->getComposeData()['services']['database'];
        self::assertSame('mariadb:latest', $mariadb['image']);
        self::assertSame('password', $mariadb['environment']['MYSQL_ROOT_PASSWORD']);
        self::assertSame('main', $mariadb['environment']['MYSQL_DATABASE']);
        self::assertSame(['3306'], $mariadb['ports']);

        $this->app->assertComposeFileIsValid();
    }

    #[LegacyCase('MakeDockerDatabaseTest::it_creates_postgresql')]
    public function testItCreatesPostgresql()
    {
        $this->app->runMaker()
            ->answer('Which database service will you be creating?', '2')
            ->acceptDefault('What version would you like to use?')
            ->run()
            ->assertOutputContains('Success')
            ->assertCreated('compose.yaml');

        $postgres = $this->getComposeData()['services']['database'];
        self::assertSame('postgres:alpine', $postgres['image']);
        self::assertSame('main', $postgres['environment']['POSTGRES_USER']);
        self::assertSame('main', $postgres['environment']['POSTGRES_PASSWORD']);
        self::assertSame('main', $postgres['environment']['POSTGRES_DB']);
        self::assertSame(['5432'], $postgres['ports']);

        $this->app->assertComposeFileIsValid();
    }

    /**
     * @return array<string, mixed>
     */
    private function getComposeData(): array
    {
        $manipulator = new ComposeFileManipulator($this->app->readFile('compose.yaml'));
        self::assertTrue($manipulator->serviceExists('database'));

        return $manipulator->getComposeData();
    }
}
