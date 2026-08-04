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

use Symfony\Bundle\MakerBundle\Maker\MakeMigration;
use Symfony\Bundle\MakerBundle\Tests\Harness\Application\Profiles;
use Symfony\Bundle\MakerBundle\Tests\Harness\Attribute\LegacyCase;
use Symfony\Bundle\MakerBundle\Tests\Harness\Attribute\MakerTest;
use Symfony\Bundle\MakerBundle\Tests\Harness\Attribute\RequiresPackage;
use Symfony\Bundle\MakerBundle\Tests\Harness\Attribute\WindowsSmoke;
use Symfony\Bundle\MakerBundle\Tests\Harness\MakerTestCase;
use Symfony\Bundle\MakerBundle\Util\CliOutputHelper;

#[MakerTest(maker: MakeMigration::class, profile: Profiles::MEGA)]
final class MakeMigrationTest extends MakerTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->app->copyFixture('SpicyFood.php', 'src/Entity/SpicyFood.php');
        $this->app->prepareDatabase(createSchema: false);
    }

    #[LegacyCase('MakeMigrationTest::it_generates_migration_with_changes')]
    #[WindowsSmoke]
    public function testItGeneratesMigrationWithChanges()
    {
        $result = $this->app->runMaker()
            ->run()
            ->assertOutputContains('Success');

        $migrations = array_values(array_filter($result->created(), static fn ($p) => str_starts_with($p, 'migrations/')));
        self::assertCount(1, $migrations);
        $result->assertOutputContains($migrations[0]);

        $this->app->runMigrations();
    }

    #[LegacyCase('MakeMigrationTest::it_detects_symfony_cli_usage')]
    #[WindowsSmoke]
    public function testItDetectsSymfonyCliUsage()
    {
        $this->app->runMaker()
            ->env([CliOutputHelper::ENV_VERSION => '0.0.0', CliOutputHelper::ENV_BIN_NAME => 'symfony'])
            ->run()
            ->assertOutputContains('symfony console doctrine:migrations:migrate');

        $this->app->runMigrations();
    }

    #[LegacyCase('MakeMigrationTest::it_detects_symfony_cli_is_not_used')]
    #[WindowsSmoke]
    public function testItDetectsSymfonyCliIsNotUsed()
    {
        $this->app->runMaker()
            ->run()
            ->assertOutputContains('php bin/console doctrine:migrations:migrate');

        $this->app->runMigrations();
    }

    #[LegacyCase('MakeMigrationTest::it_generates_migration_with_no_changes')]
    public function testItGeneratesMigrationWithNoChanges()
    {
        // sync the DEV database: the one make:migration diffs against,
        // so there are no changes
        $this->app->updateSchema(env: 'dev');

        $result = $this->app->runMaker()->run();

        self::assertStringNotContainsString('Success', $result->output());
        $result->assertOutputContains('No database changes were detected');
    }

    #[LegacyCase('MakeMigrationTest::it_asks_previous_migration_question')]
    #[RequiresPackage('doctrine/doctrine-migrations-bundle', '>=3')]
    public function testItAsksPreviousMigrationQuestion()
    {
        $this->app->runConsole('make:migration');
        $setupMigrations = array_map('basename', glob($this->app->getPath('migrations/*.php')));

        $result = $this->app->runMaker()
            ->answer('Are you sure you wish to continue', 'y')
            ->run();

        $result->assertOutputContains('You have 1 available migrations to execute');
        $result->assertOutputContains('Are you sure you wish to continue?');
        $result->assertOutputContains('Success');

        // the tested run's artifact is the SECOND migration; the setup one
        // duplicates its DDL and the two could never both apply
        foreach ($setupMigrations as $file) {
            $this->app->deleteFile('migrations/'.$file);
        }

        $this->app->runMigrations();
    }

    #[LegacyCase('MakeMigrationTest::it_asks_previous_migration_question_and_decline')]
    #[RequiresPackage('doctrine/doctrine-migrations-bundle', '>=3')]
    public function testItAsksPreviousMigrationQuestionAndDecline()
    {
        $this->app->runConsole('make:migration');

        $result = $this->app->runMaker()
            ->answer('Are you sure you wish to continue', 'n')
            ->run();

        self::assertStringNotContainsString('Success', $result->output());
    }

    #[LegacyCase('MakeMigrationTest::it_generates_a_formatted_migration')]
    #[RequiresPackage('doctrine/doctrine-migrations-bundle', '>=3')]
    public function testItGeneratesAFormattedMigration()
    {
        // unlike the legacy test, the option goes to the maker run under test:
        // a second unformatted migration would only duplicate the DDL and
        // could never actually be executed
        $this->app->runMaker()
            ->arguments('--formatted')
            ->run()
            ->assertOutputContains('Success');

        $this->app->runMigrations();
    }

    #[LegacyCase('MakeMigrationTest::it_generates_a_nowdoc_migration')]
    #[RequiresPackage('doctrine/doctrine-migrations-bundle', '>=3')]
    public function testItGeneratesANowdocMigration()
    {
        $this->app->runMaker()
            ->arguments('--nowdoc')
            ->run()
            ->assertOutputContains('Success');

        $this->app->runMigrations();
    }
}
