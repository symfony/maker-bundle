<?php

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
            ->addExtraDependencies('doctrine/orm')
            ->assert(function (string $output, string $directory) {
                $this->assertStringContainsString('Success', $output);

                $finder = new Finder();
                $finder->in($directory.'/src/Migrations')
                    ->name('*.php');
                $this->assertCount(1, $finder);

                // see that the exact filename is in the output
                $iterator = $finder->getIterator();
                $iterator->rewind();
                $this->assertStringContainsString(sprintf('"src/Migrations/%s"', $iterator->current()->getFilename()), $output);
            }),
        ];

        yield 'migration_no_changes' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeMigration::class),
            [/* no input */])
            ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeMigration')
            ->configureDatabase()
            // sync the database, so no changes are needed
            ->addExtraDependencies('doctrine/orm')
            ->assert(function (string $output, string $directory) {
                $this->assertNotContains('Success', $output);

                $this->assertStringContainsString('No database changes were detected', $output);
            }),
        ];
    }
}
