<?php

/*
 * This file is part of the Symfony MakerBundle package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\MakerBundle\Tests\Harness\Application;

/**
 * The single place application profiles are declared.
 *
 * Most tests share the rich MEGA app: makers behave like they do in a
 * real-world application with everything installed, and workers only pay one
 * materialization instead of one per specialized profile. The remaining
 * profiles are lean GUARDS whose deliberate ABSENCES are the point (no twig,
 * no live-component, no mercure, no translator, ...): presence-dependent
 * wizard branches and error paths can only be proven on an app that really
 * lacks the package.
 *
 * Every direct requirement carries a Reason so the dependency-declaration
 * contract suite can verify the relationship in both directions.
 */
final class ProfileCatalog
{
    public static function testDatabaseDsn(): string
    {
        return $_SERVER['TEST_DATABASE_DSN'] ?? getenv('TEST_DATABASE_DSN') ?: 'sqlite:///%kernel.project_dir%/var/app.db';
    }

    public static function get(string $name): Profile
    {
        $profiles = self::all();
        if (!isset($profiles[$name])) {
            throw new \InvalidArgumentException(\sprintf('Unknown app profile "%s". Known profiles: "%s". Add new profiles in "%s".', $name, implode(', ', array_keys($profiles)), self::class));
        }

        return $profiles[$name];
    }

    /**
     * @return array<string, Profile>
     */
    public static function all(): array
    {
        return [
            // the "nothing installed" guard: no twig (make:controller's
            // no-template variant), no api-platform (make:test's
            // LegacyApiTestCase branch), no doctrine, no security
            Profiles::BASE => new Profile(
                name: Profiles::BASE,
                requireDev: [
                    'phpunit/phpunit' => ['reason' => Reason::HARNESS_ONLY, 'version' => '^10.5|^11.5'],
                    'symfony/phpunit-bridge' => ['reason' => Reason::HARNESS_ONLY],
                    'symfony/browser-kit' => ['reason' => Reason::HARNESS_ONLY],
                    'symfony/css-selector' => ['reason' => Reason::HARNESS_ONLY],
                ],
                patches: [
                    // the app suite must surface its own deprecations, see #237
                    ['file' => '.env.test', 'find' => 'SYMFONY_DEPRECATIONS_HELPER=999999', 'replace' => 'SYMFONY_DEPRECATIONS_HELPER=max[self]=0', 'allowMissing' => true],
                    ['file' => 'phpunit.xml.dist', 'find' => '<server name="SYMFONY_PHPUNIT_VERSION" value="9.6" />', 'replace' => '', 'allowMissing' => true],
                ],
            ),

            Profiles::MEGA => new Profile(
                name: Profiles::MEGA,
                extends: Profiles::BASE,
                require: [
                    'symfony/validator' => ['reason' => Reason::DECLARED_BY_MAKER],
                    'symfony/serializer' => ['reason' => Reason::DECLARED_BY_MAKER],
                    // part of what the flex "serializer" alias (serializer-pack)
                    // installs; without it FrameworkExtension removes
                    // serializer.normalizer.object and generated normalizers
                    // break the app container
                    'symfony/property-access' => ['reason' => Reason::DECLARED_BY_MAKER],
                    'symfony/messenger' => ['reason' => Reason::DECLARED_BY_MAKER],
                    'symfony/scheduler' => ['reason' => Reason::RUNTIME_INSTALLER],
                    'symfony/webhook' => ['reason' => Reason::RUNTIME_INSTALLER],
                    'symfony/uid' => ['reason' => Reason::TEST_ONLY],
                    // MakeWebhook auto-installs it for ExpressionRequestMatcher
                    'symfony/expression-language' => ['reason' => Reason::RUNTIME_INSTALLER],

                    // what the flex "orm" alias installs
                    'symfony/orm-pack' => ['reason' => Reason::DECLARED_BY_MAKER],
                    'doctrine/doctrine-migrations-bundle' => ['reason' => Reason::DECLARED_BY_MAKER],

                    'symfony/twig-bundle' => ['reason' => Reason::DECLARED_BY_MAKER],
                    'symfony/form' => ['reason' => Reason::DECLARED_BY_MAKER],
                    'symfony/security-bundle' => ['reason' => Reason::DECLARED_BY_MAKER],
                    'symfony/security-csrf' => ['reason' => Reason::DECLARED_BY_MAKER],
                    'symfony/mailer' => ['reason' => Reason::DECLARED_BY_MAKER],
                    'symfonycasts/reset-password-bundle' => ['reason' => Reason::DECLARED_BY_MAKER],
                    // MakeRegistrationForm gates on it at runtime
                    // (checkComponentsExist) instead of configureDependencies()
                    'symfonycasts/verify-email-bundle' => ['reason' => Reason::TEST_ONLY],

                    // MakeEntity only branches on these when they are installed
                    // (the ApiResource and Broadcast wizard questions); it never
                    // declares them via configureDependencies().
                    // every api-platform 4.x release requires PHP >= 8.2, so the
                    // lowest-deps leg (PHP 8.1) must be allowed to fall back to 3.4
                    'api-platform/core' => ['reason' => Reason::TEST_ONLY] + (\PHP_VERSION_ID < 80200 ? ['version' => '^3.4'] : []),
                    'symfony/ux-turbo' => ['reason' => Reason::RUNTIME_INSTALLER],
                    // broadcasting entities publishes to a real hub at runtime
                    'symfony/mercure-bundle' => ['reason' => Reason::TEST_ONLY],

                    'symfony/ux-twig-component' => ['reason' => Reason::DECLARED_BY_MAKER],
                    'symfony/ux-live-component' => ['reason' => Reason::TEST_ONLY],
                    'symfony/stimulus-bundle' => ['reason' => Reason::DECLARED_BY_MAKER],
                ],
                requireDev: [
                    'doctrine/doctrine-fixtures-bundle' => ['reason' => Reason::DECLARED_BY_MAKER],
                    // the registration/reset-password fixture tests assert queued
                    // emails through the test-env profiler
                    'symfony/web-profiler-bundle' => ['reason' => Reason::TEST_ONLY],
                    // the NotCompromisedPassword validator service wires it (the
                    // actual HIBP call is disabled in test via validation_test.yaml)
                    'symfony/http-client' => ['reason' => Reason::TEST_ONLY],
                ],
                files: [
                    ...self::twigTracerFiles(),
                    // no HIBP API calls from generated NotCompromisedPassword constraints
                    'config/packages/harness_validation_test.yaml' => __DIR__.'/../resources/app/validation_test.yaml',
                ],
                patches: self::doctrineDatabasePatches(),
            ),

            Profiles::MEGA_CUSTOM_NAMESPACE => new Profile(
                name: Profiles::MEGA_CUSTOM_NAMESPACE,
                extends: Profiles::MEGA,
                rootNamespace: 'Custom',
            ),

            // the absence guard for everything doctrine/twig-flavored: api-platform
            // AND ux-turbo WITHOUT mercure (MakeEntity's broadcast-requires-mercure
            // error path), ux-twig-component WITHOUT ux-live-component
            // (MakeTwigComponent's "install ux-live-component" error path). The
            // absences are compatible, so one app carries both guards.
            Profiles::GUARD => new Profile(
                name: Profiles::GUARD,
                extends: Profiles::BASE,
                require: [
                    // what the flex "orm" alias installs; the entity guard tests
                    // really persist through the schema
                    'symfony/orm-pack' => ['reason' => Reason::DECLARED_BY_MAKER],
                    // renders component and broadcast templates in the nested tests
                    'symfony/twig-bundle' => ['reason' => Reason::TEST_ONLY],
                    'symfony/ux-twig-component' => ['reason' => Reason::DECLARED_BY_MAKER],
                    // MakeEntity only branches on these when they are installed;
                    // every api-platform 4.x release requires PHP >= 8.2, so the
                    // lowest-deps leg (PHP 8.1) must be allowed to fall back to 3.4
                    'api-platform/core' => ['reason' => Reason::TEST_ONLY] + (\PHP_VERSION_ID < 80200 ? ['version' => '^3.4'] : []),
                    'symfony/ux-turbo' => ['reason' => Reason::RUNTIME_INSTALLER],
                ],
                files: self::twigTracerFiles(),
                patches: self::doctrineDatabasePatches(),
            ),

            // makers only branch on the translator when it is installed; the
            // MEGA app deliberately has none so the untranslated variants keep
            // their home there
            Profiles::I18N => new Profile(
                name: Profiles::I18N,
                extends: Profiles::MEGA,
                require: [
                    'symfony/translation' => ['reason' => Reason::TEST_ONLY],
                ],
            ),

            Profiles::PANTHER => new Profile(
                name: Profiles::PANTHER,
                extends: Profiles::BASE,
                requireDev: [
                    // MakeTest only declares "panther" for the PantherTestCase
                    // input scenario (conditional, not visible to the contract)
                    'symfony/panther' => ['reason' => Reason::TEST_ONLY],
                    'dbrekelmans/bdi' => ['reason' => Reason::HARNESS_ONLY],
                ],
                patches: [
                    // the recipe boots panther's web server with APP_ENV=panther,
                    // which the SF8 skeleton kernel rejects (getAllowedEnvs());
                    // use the app's test env instead
                    ['file' => '.env.test', 'find' => 'PANTHER_APP_ENV=panther', 'replace' => 'PANTHER_APP_ENV=test'],
                ],
                postBuild: [
                    // browser driver downloaded at PROFILE BUILD time, never in tests
                    ['php', 'vendor/dbrekelmans/bdi/bdi', 'detect', 'drivers'],
                ],
            ),

            // php-cs-fixer is quarantined here: the template linter auto-detects
            // the app's own fixer, so its presence would reroute the linting of
            // EVERY generated file in any app that carried it. The linter is
            // exercised through make:voter, which declares the security bundle.
            Profiles::PHPCSFIXER => new Profile(
                name: Profiles::PHPCSFIXER,
                extends: Profiles::BASE,
                require: [
                    'symfony/security-bundle' => ['reason' => Reason::DECLARED_BY_MAKER],
                    'php-cs-fixer/shim' => ['reason' => Reason::TEST_ONLY],
                ],
            ),
        ];
    }

    /**
     * Test-env Twig tracer: makes template loads observable for the execution
     * contract in every twig-bearing profile.
     *
     * @return array<string, string>
     */
    private static function twigTracerFiles(): array
    {
        return [
            'tests/Harness/TwigTracerLoader.php' => __DIR__.'/../resources/app/TwigTracerLoader.php',
            'config/services_test.yaml' => __DIR__.'/../resources/app/services_test.yaml',
        ];
    }

    /**
     * @return list<array{file: string, find: string, replace: string}>
     */
    private static function doctrineDatabasePatches(): array
    {
        return [
            // the recipe's dbname_suffix ('_test%env(default::TEST_TOKEN)%')
            // stays: it isolates parallel workers per database, for
            // sqlite files and real servers alike
            // resolved here so the DSN is part of the profile fingerprint
            ['file' => '.env', 'find' => 'postgresql://app:!ChangeMe!@127.0.0.1:5432/app?serverVersion=16&charset=utf8', 'replace' => self::testDatabaseDsn()],
            // on a real database server, parallel workers also need
            // their own DEV database: makers run in the dev env (e.g.
            // make:migration diffs the dev schema) and the recipe only
            // isolates the test env. Not applied to sqlite: worker
            // dirs already isolate file databases, and the sqlite
            // cleanup glob only knows the recipe's _test suffix.
            ...(str_starts_with(self::testDatabaseDsn(), 'sqlite') ? [] : [
                ['file' => 'config/packages/doctrine.yaml', 'find' => 'when@test:', 'replace' => "when@dev:\n    doctrine:\n        dbal:\n            dbname_suffix: '_dev%env(default::TEST_TOKEN)%'\n\nwhen@test:"],
            ]),
        ];
    }
}
