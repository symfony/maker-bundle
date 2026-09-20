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

use Symfony\Bundle\MakerBundle\Maker\MakeCi;
use Symfony\Bundle\MakerBundle\Test\MakerTestCase;
use Symfony\Bundle\MakerBundle\Test\MakerTestRunner;

final class MakeCiTest extends MakerTestCase
{
    protected function getMakerClass(): string
    {
        return MakeCi::class;
    }

    public static function getTestDetails(): \Generator
    {
        yield 'it_generates_github_actions' => [self::buildMakerTest()
            ->addExtraDependencies('opis/json-schema')
            ->run(static function (MakerTestRunner $runner) {
                $output = $runner->runMaker([
                    '0', // GitHub Actions
                    '', // PHP version (default)
                    '', // default branch (default)
                ]);

                self::assertStringContainsString('Success', $output);
                self::assertFileExists($runner->getPath('.github/workflows/ci.yaml'));

                $ciContent = file_get_contents($runner->getPath('.github/workflows/ci.yaml'));
                self::assertStringContainsString('name: CI', $ciContent);
                self::assertStringContainsString('lint:', $ciContent);
                self::assertStringContainsString('Warm production cache', $ciContent);
                self::assertStringContainsString('Lint YAML', $ciContent);
                self::assertStringContainsString('Lint container', $ciContent);

                self::assertFileExists($runner->getPath('.github/dependabot.yml'));
                $dependabotContent = file_get_contents($runner->getPath('.github/dependabot.yml'));
                self::assertStringContainsString('package-ecosystem: github-actions', $dependabotContent);

                self::lintYaml($runner, '.github/workflows/ci.yaml', 'github-workflow.json');
                self::lintYaml($runner, '.github/dependabot.yml', 'dependabot-2.0.json');
            }),
        ];

        yield 'it_generates_gitlab_ci' => [self::buildMakerTest()
            ->addExtraDependencies('opis/json-schema')
            ->run(static function (MakerTestRunner $runner) {
                $output = $runner->runMaker([
                    '1', // GitLab CI/CD
                    '', // PHP version (default)
                    '', // default branch (default)
                ]);

                self::assertStringContainsString('Success', $output);
                self::assertFileExists($runner->getPath('.gitlab-ci.yml'));

                $ciContent = file_get_contents($runner->getPath('.gitlab-ci.yml'));
                self::assertStringContainsString('stages:', $ciContent);
                self::assertStringContainsString('lint:', $ciContent);

                self::lintYaml($runner, '.gitlab-ci.yml', 'gitlab-ci.json');
            }),
        ];

        yield 'it_generates_github_actions_with_platform_option' => [self::buildMakerTest()
            ->run(static function (MakerTestRunner $runner) {
                $output = $runner->runMaker([], '--platform=github-actions --no-interaction');

                self::assertStringContainsString('Success', $output);
                self::assertFileExists($runner->getPath('.github/workflows/ci.yaml'));
            }),
        ];

        yield 'it_detects_phpunit' => [self::buildMakerTest()
            ->run(static function (MakerTestRunner $runner) {
                $output = $runner->runMaker([], '--platform=github-actions --no-interaction');

                $ciContent = file_get_contents($runner->getPath('.github/workflows/ci.yaml'));
                self::assertStringContainsString('tests:', $ciContent);
                self::assertStringContainsString('vendor/bin/phpunit', $ciContent);
            }),
        ];

        yield 'it_detects_php_version' => [self::buildMakerTest()
            ->run(static function (MakerTestRunner $runner) {
                $output = $runner->runMaker([], '--platform=github-actions --no-interaction');

                $ciContent = file_get_contents($runner->getPath('.github/workflows/ci.yaml'));
                self::assertMatchesRegularExpression("/PHP_VERSION: '\d+\.\d+'/", $ciContent);
            }),
        ];

        yield 'it_generates_with_php_version_and_branch_options' => [self::buildMakerTest()
            ->run(static function (MakerTestRunner $runner) {
                $runner->runMaker([], '--platform=github-actions --php-version=8.3 --branch=develop --no-interaction');

                $ciContent = file_get_contents($runner->getPath('.github/workflows/ci.yaml'));
                self::assertStringContainsString("PHP_VERSION: '8.3'", $ciContent);
                self::assertStringContainsString("- 'develop'", $ciContent);
            }),
        ];

        yield 'it_generates_github_actions_with_postgres' => [self::buildMakerTest()
            ->addExtraDependencies('orm', 'opis/json-schema')
            ->run(static function (MakerTestRunner $runner) {
                $runner->runMaker([], '--platform=github-actions --database=postgres --no-interaction');

                $ciContent = file_get_contents($runner->getPath('.github/workflows/ci.yaml'));
                self::assertStringContainsString('services:', $ciContent);
                self::assertStringContainsString('image: postgres:16-alpine', $ciContent);
                self::assertStringContainsString("DATABASE_URL: 'postgresql://app:password@127.0.0.1:5432/app", $ciContent);
                self::assertStringContainsString('Setup database', $ciContent);
                self::assertStringContainsString('doctrine:database:create --if-not-exists', $ciContent);

                self::lintYaml($runner, '.github/workflows/ci.yaml', 'github-workflow.json');
            }),
        ];

        yield 'it_generates_gitlab_ci_with_mysql' => [self::buildMakerTest()
            ->addExtraDependencies('orm', 'opis/json-schema')
            ->run(static function (MakerTestRunner $runner) {
                $runner->runMaker([], '--platform=gitlab-ci --database=mysql --no-interaction');

                $ciContent = file_get_contents($runner->getPath('.gitlab-ci.yml'));
                self::assertStringContainsString('name: mysql:8.0', $ciContent);
                self::assertStringContainsString('pdo_mysql', $ciContent);
                self::assertStringContainsString("DATABASE_URL: 'mysql://root:password@mysql:3306/app", $ciContent);
                self::assertStringContainsString('doctrine:database:create --if-not-exists', $ciContent);

                self::lintYaml($runner, '.gitlab-ci.yml', 'gitlab-ci.json');
            }),
        ];

        yield 'it_generates_github_actions_with_sqlite' => [self::buildMakerTest()
            ->addExtraDependencies('orm', 'opis/json-schema')
            ->run(static function (MakerTestRunner $runner) {
                $runner->runMaker([], '--platform=github-actions --database=sqlite --no-interaction');

                $ciContent = file_get_contents($runner->getPath('.github/workflows/ci.yaml'));
                self::assertStringNotContainsString('services:', $ciContent);
                self::assertStringNotContainsString('DATABASE_URL', $ciContent);
                self::assertStringContainsString('Setup database', $ciContent);
                // SQLite creates the database file on first connection, and DBAL 4 can't list SQLite databases
                self::assertStringNotContainsString('doctrine:database:create', $ciContent);

                self::lintYaml($runner, '.github/workflows/ci.yaml', 'github-workflow.json');
            }),
        ];

        yield 'it_generates_github_actions_without_database' => [self::buildMakerTest()
            ->addExtraDependencies('orm')
            ->run(static function (MakerTestRunner $runner) {
                $runner->runMaker([], '--platform=github-actions --database=none --no-interaction');

                $ciContent = file_get_contents($runner->getPath('.github/workflows/ci.yaml'));
                self::assertStringNotContainsString('services:', $ciContent);
                self::assertStringNotContainsString('Setup database', $ciContent);
            }),
        ];

        yield 'it_detects_doctrine_from_orm_pack' => [self::buildMakerTest()
            ->run(static function (MakerTestRunner $runner) {
                // Simulate a symfony/orm-pack that was not unpacked
                $runner->replaceInFile('composer.json', '"require": {', "\"require\": {\n        \"symfony/orm-pack\": \"*\",");

                $runner->runMaker([], '--platform=github-actions --database=postgres --no-interaction');

                $ciContent = file_get_contents($runner->getPath('.github/workflows/ci.yaml'));
                self::assertStringContainsString('image: postgres:16-alpine', $ciContent);
                self::assertStringContainsString('doctrine:migrations:migrate', $ciContent);
            }),
        ];

        yield 'it_does_not_overwrite_an_existing_ci_file' => [self::buildMakerTest()
            ->run(static function (MakerTestRunner $runner) {
                $runner->writeFile('.github/workflows/ci.yaml', "# edited by hand\n");

                $output = $runner->runMaker([], '--platform=github-actions --no-interaction', allowedToFail: true);

                self::assertStringContainsString('already exists', $output);
                self::assertSame("# edited by hand\n", file_get_contents($runner->getPath('.github/workflows/ci.yaml')));
            }),
        ];

        yield 'it_rejects_missing_platform_non_interactively' => [self::buildMakerTest()
            ->run(static function (MakerTestRunner $runner) {
                $output = $runner->runMaker([], '--no-interaction', allowedToFail: true);

                self::assertStringContainsString('--platform', $output);
                self::assertFileDoesNotExist($runner->getPath('.github/workflows/ci.yaml'));
                self::assertFileDoesNotExist($runner->getPath('.gitlab-ci.yml'));
            }),
        ];

        yield 'it_rejects_unknown_database' => [self::buildMakerTest()
            ->addExtraDependencies('orm')
            ->run(static function (MakerTestRunner $runner) {
                $output = $runner->runMaker([], '--platform=github-actions --database=oracle --no-interaction', allowedToFail: true);

                self::assertStringContainsString('Unknown database "oracle"', $output);
                self::assertFileDoesNotExist($runner->getPath('.github/workflows/ci.yaml'));
            }),
        ];

        yield 'it_rejects_invalid_php_version' => [self::buildMakerTest()
            ->run(static function (MakerTestRunner $runner) {
                $output = $runner->runMaker([], '--platform=github-actions --php-version=latest --no-interaction', allowedToFail: true);

                self::assertStringContainsString('Invalid PHP version "latest"', $output);
                self::assertFileDoesNotExist($runner->getPath('.github/workflows/ci.yaml'));
            }),
        ];
    }

    private static function lintYaml(MakerTestRunner $runner, string $file, string $schema): void
    {
        $runner->runProcess('php bin/console lint:yaml '.$file);

        // JSON Schema validation is available since Symfony 8.2
        if ($runner->getSymfonyVersion() < 80200) {
            return;
        }

        $runner->copy('make-ci/schemas/'.$schema, 'var/schemas/'.$schema);
        $runner->runProcess(\sprintf('php bin/console lint:yaml %s --check-schema=var/schemas/%s', $file, $schema));
    }
}
