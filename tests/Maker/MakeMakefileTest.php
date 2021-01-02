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

use Symfony\Bundle\MakerBundle\Maker\MakeMakefile;
use Symfony\Bundle\MakerBundle\Test\MakerTestCase;
use Symfony\Bundle\MakerBundle\Test\MakerTestDetails;
use Symfony\Component\Filesystem\Filesystem;

/**
 * @author Steven Dubois <contact@duboiss.fr>
 * @author Aymeric Gueracague <aymeric.gueracague@gmail.com>
 */
class MakeMakefileTest extends MakerTestCase
{
    public function getTestDetails(): \Generator
    {
        yield 'makefile_basic' => [
            MakerTestDetails::createTest(
                $this->getMakerInstance(MakeMakefile::class),
                []
            )
                ->assert(function (string $output, string $directory) {
                    $fs = new Filesystem();
                    $makefilePath = sprintf('%s/Makefile', $directory);

                    $this->assertTrue($fs->exists($makefilePath));
                }),
        ];

        yield 'makefile_already_exists_in_root' => [
            MakerTestDetails::createTest(
                $this->getMakerInstance(MakeMakefile::class),
                [
                    'yes', // Regenerate it, default is no
                ]
            )
                ->addPreMakeCommand('touch Makefile')
                ->assert(function (string $output, string $directory) {
                    $fs = new Filesystem();
                    $makefilePath = sprintf('%s/Makefile', $directory);
                    $oldMakefilePath = sprintf('%s/Makefile.old', $directory);

                    $this->assertTrue($fs->exists($makefilePath));
                    $this->assertTrue($fs->exists($oldMakefilePath));
                }),
        ];

        yield 'makefile_with_twig' => [
            MakerTestDetails::createTest(
                $this->getMakerInstance(MakeMakefile::class),
                []
            )
                ->addExtraDependencies('twig/twig')
                ->assert(function (string $output, string $directory) {
                    $fs = new Filesystem();
                    $makefilePath = sprintf('%s/Makefile', $directory);
                    $makefile = file_get_contents($makefilePath);

                    $this->assertTrue($fs->exists($makefilePath));
                    $this->assertStringContainsString('Twig detected', $output);
                    $this->assertStringContainsString('.PHONY: lint lint-container lint-twig lint-yaml', $makefile);
                }),
        ];

        yield 'makefile_with_doctrine' => [
            MakerTestDetails::createTest(
                $this->getMakerInstance(MakeMakefile::class),
                []
            )
                ->addExtraDependencies('symfony/orm-pack')
                ->assert(function (string $output, string $directory) {
                    $fs = new Filesystem();
                    $makefilePath = sprintf('%s/Makefile', $directory);
                    $makefile = file_get_contents($makefilePath);

                    $this->assertTrue($fs->exists($makefilePath));
                    $this->assertStringContainsString('Doctrine detected', $output);
                    $this->assertStringContainsString('.PHONY: db db-reset db-cache db-validate', $makefile);
                }),
        ];

        yield 'makefile_with_doctrine_and_fixtures' => [
            MakerTestDetails::createTest(
                $this->getMakerInstance(MakeMakefile::class),
                []
            )
                ->addExtraDependencies('symfony/orm-pack')
                ->addExtraDependencies('doctrine/doctrine-fixtures-bundle')
                ->assert(function (string $output, string $directory) {
                    $fs = new Filesystem();
                    $makefilePath = sprintf('%s/Makefile', $directory);
                    $makefile = file_get_contents($makefilePath);

                    $this->assertTrue($fs->exists($makefilePath));
                    $this->assertStringContainsString('Doctrine detected', $output);
                    $this->assertStringContainsString('Doctrine fixtures bundle detected', $output);
                    $this->assertStringContainsString('.PHONY: db db-reset db-cache db-validate fixtures', $makefile);
                }),
        ];

        yield 'makefile_with_doctrine_sqlite' => [
            MakerTestDetails::createTest(
                $this->getMakerInstance(MakeMakefile::class),
                []
            )
                ->addExtraDependencies('symfony/orm-pack')
                ->addReplacement(
                    '.env',
                    'DATABASE_URL="postgresql://db_user:db_password@127.0.0.1:5432/db_name?serverVersion=13&charset=utf8"',
                    'DATABASE_URL="sqlite:///%kernel.project_dir%/var/app.db"'
                )
                ->assert(function (string $output, string $directory) {
                    $fs = new Filesystem();
                    $makefilePath = sprintf('%s/Makefile', $directory);
                    $makefile = file_get_contents($makefilePath);

                    $this->assertTrue($fs->exists($makefilePath));
                    // --if-exits flag can't be use on sqlite database
                    $this->assertStringContainsString('@-$(SYMFONY) doctrine:database:drop --force', $makefile);
                }),
        ];

        yield 'makefile_with_webpack_encore_and_no_pm_initialised' => [
            MakerTestDetails::createTest(
                $this->getMakerInstance(MakeMakefile::class),
                [
                    '', // Default package manager is Yarn
                ]
            )
                ->addPreMakeCommand('touch webpack.config.js')
                ->assert(function (string $output, string $directory) {
                    $fs = new Filesystem();
                    $makefilePath = sprintf('%s/Makefile', $directory);
                    $makefile = file_get_contents($makefilePath);

                    $this->assertTrue($fs->exists($makefilePath));
                    $this->assertStringContainsString('Webpack Encore detected', $output);
                    $this->assertStringContainsString('yarn.lock: package.json', $makefile);
                }),
        ];

        yield 'makefile_with_webpack_encore_and_yarn' => [
            MakerTestDetails::createTest(
                $this->getMakerInstance(MakeMakefile::class),
                []
            )
                ->addPreMakeCommand('touch yarn.lock')
                ->addPreMakeCommand('touch webpack.config.js')
                ->assert(function (string $output, string $directory) {
                    $fs = new Filesystem();
                    $makefilePath = sprintf('%s/Makefile', $directory);
                    $makefile = file_get_contents($makefilePath);

                    $this->assertTrue($fs->exists($makefilePath));
                    $this->assertStringContainsString('Webpack Encore detected', $output);
                    $this->assertStringContainsString('yarn.lock: package.json', $makefile);
                }),
        ];

        yield 'makefile_with_webpack_encore_and_npm' => [
            MakerTestDetails::createTest(
                $this->getMakerInstance(MakeMakefile::class),
                []
            )
                ->addPreMakeCommand('touch package-lock.json')
                ->addPreMakeCommand('touch webpack.config.js')
                ->assert(function (string $output, string $directory) {
                    $fs = new Filesystem();
                    $makefilePath = sprintf('%s/Makefile', $directory);
                    $makefile = file_get_contents($makefilePath);

                    $this->assertTrue($fs->exists($makefilePath));
                    $this->assertStringContainsString('Webpack Encore detected', $output);
                    $this->assertStringContainsString('package-lock.json: package.json', $makefile);
                }),
        ];

        yield 'makefile_with_webpack_encore_and_yarn_and_npm' => [
            MakerTestDetails::createTest(
                $this->getMakerInstance(MakeMakefile::class),
                [
                    '', // Yarn by default
                ]
            )
                ->addPreMakeCommand('touch package-lock.json')
                ->addPreMakeCommand('touch yarn.lock')
                ->addPreMakeCommand('touch webpack.config.js')
                ->assert(function (string $output, string $directory) {
                    $fs = new Filesystem();
                    $makefilePath = sprintf('%s/Makefile', $directory);
                    $makefile = file_get_contents($makefilePath);

                    $this->assertTrue($fs->exists($makefilePath));
                    $this->assertStringContainsString('Webpack Encore detected', $output);
                    $this->assertStringContainsString('yarn.lock: package.json', $makefile);
                }),
        ];

        yield 'makefile_with_webpack_encore_with_yarn_and_doctrine' => [
            MakerTestDetails::createTest(
                $this->getMakerInstance(MakeMakefile::class),
                []
            )
                ->addExtraDependencies('symfony/orm-pack')
                ->addPreMakeCommand('touch yarn.lock')
                ->addPreMakeCommand('touch webpack.config.js')
                ->assert(function (string $output, string $directory) {
                    $fs = new Filesystem();
                    $makefilePath = sprintf('%s/Makefile', $directory);
                    $makefile = file_get_contents($makefilePath);

                    $this->assertTrue($fs->exists($makefilePath));
                    $this->assertStringContainsString('Webpack Encore detected', $output);
                    $this->assertStringContainsString('yarn.lock: package.json', $makefile);
                    $this->assertStringContainsString('.PHONY: db db-reset db-cache db-validate', $makefile);
                }),
        ];
    }
}
