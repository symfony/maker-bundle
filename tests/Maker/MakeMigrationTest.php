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

use Symfony\Bundle\MakerBundle\Maker\MakeMigration;
use Symfony\Bundle\MakerBundle\Test\MakerTestCase;
use Symfony\Bundle\MakerBundle\Test\MakerTestDetails;
use Symfony\Component\Finder\Finder;

class MakeMigrationTest extends MakerTestCase
{
    public function getTestDetails()
    {
        yield 'migration_with_changes' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeMigration::class),
            [/* no input */])
            ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeMigration')
            ->configureDatabase(false)
            // doctrine-migrations-bundle only requires doctrine-bundle, which
            // only requires doctrine/dbal. But we're testing with the ORM,
            // so let's install it
            ->addExtraDependencies('doctrine/orm:@stable')
            ->assert(function (string $output, string $directory) {
                $this->assertStringContainsString('Success', $output);

                // support for Migrations 3 (/migrations) and earlier
                $migrationsDirectoryPath = file_exists($directory.'/migrations') ? 'migrations' : 'src/Migrations';

                $finder = new Finder();
                $finder->in($directory.'/'.$migrationsDirectoryPath)
                    ->name('*.php');
                $this->assertCount(1, $finder);

                // see that the exact filename is in the output
                $iterator = $finder->getIterator();
                $iterator->rewind();
                $this->assertStringContainsString(sprintf('"%s/%s"', $migrationsDirectoryPath, $iterator->current()->getFilename()), $output);
            }),
        ];

        yield 'migration_no_changes' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeMigration::class),
            [/* no input */])
            ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeMigration')
            // sync the database, so no changes are needed
            ->configureDatabase()
            ->addExtraDependencies('doctrine/orm:@stable')
            ->assert(function (string $output, string $directory) {
                $this->assertStringNotContainsString('Success', $output);

                $this->assertStringContainsString('No database changes were detected', $output);
            }),
        ];

        yield 'migration_with_previous_migration_question' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeMigration::class),
            [
                // confirm migration
                'y',
            ])
            ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeMigration')
            ->configureDatabase(false)
            ->addRequiredPackageVersion('doctrine/doctrine-migrations-bundle', '>=3')
            ->addExtraDependencies('doctrine/orm:@stable')
            // generate a migration first
            ->addPreMakeCommand('php bin/console make:migration')
            ->assert(function (string $output, string $directory) {
                $this->assertStringContainsString('You have 1 available migrations to execute', $output);
                $this->assertStringContainsString('Success', $output);
                $this->assertCount(14, explode("\n", $output), 'Asserting that very specific output is shown - some should be hidden');
            }),
        ];

        yield 'migration_with_previous_migration_decline_question' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeMigration::class),
            [
                // no to confirm
                'n',
            ])
            ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeMigration')
            ->configureDatabase(false)
            ->addRequiredPackageVersion('doctrine/doctrine-migrations-bundle', '>=3')
            ->addExtraDependencies('doctrine/orm:@stable')
            // generate a migration first
            ->addPreMakeCommand('php bin/console make:migration')
            ->assert(function (string $output, string $directory) {
                $this->assertStringNotContainsString('Success', $output);
            }),
        ];
    }
}
