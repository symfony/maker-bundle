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

use Symfony\Bundle\MakerBundle\Maker\MakeRegistrationForm;
use Symfony\Bundle\MakerBundle\Test\MakerTestCase;
use Symfony\Bundle\MakerBundle\Test\MakerTestDetails;
use Symfony\Bundle\MakerBundle\Test\MakerTestRunner;
use Symfony\Component\Yaml\Yaml;

class MakeRegistrationFormTest extends MakerTestCase
{
    protected function getMakerClass(): string
    {
        return MakeRegistrationForm::class;
    }

    private static function createRegistrationFormTest(): MakerTestDetails
    {
        return self::buildMakerTest()
            ->preRun(static function (MakerTestRunner $runner) {
                $runner->copy(
                    'make-registration-form/standard_setup',
                    ''
                );
            })
        ;
    }

    public static function getTestDetails(): \Generator
    {
        yield 'it_generates_registration_form_non_interactively' => [self::createRegistrationFormTest()
            ->run(static function (MakerTestRunner $runner) {
                self::makeUser($runner);

                $output = $runner->runMaker([], '--no-interaction --redirect-route=app_anonymous');

                self::assertStringContainsString('Success', $output);

                // the user class, the login field and the password field are all derived from
                // security.yaml and the class itself, exactly as the prompt derives them
                $controller = file_get_contents($runner->getPath('src/Controller/RegistrationController.php'));
                self::assertStringContainsString('app_anonymous', $controller);
                // the three confirmations are opt-in here, where the prompt offers yes
                self::assertStringNotContainsString('EmailVerifier', $controller);
                self::assertStringNotContainsString('UniqueEntity', file_get_contents($runner->getPath('src/Entity/User.php')));
            }),
        ];

        yield 'it_generates_registration_form_non_interactively_with_verification' => [self::createRegistrationFormTest()
            ->addExtraDependencies('symfonycasts/verify-email-bundle')
            ->run(static function (MakerTestRunner $runner) {
                self::makeUser($runner);

                $runner->runMaker(
                    [],
                    '--no-interaction --redirect-route=app_anonymous --unique-entity --verify-email'
                    .' --from-email-address=jr@rushlow.dev --from-email-name="Jesse Rushlow"'
                );

                $controller = file_get_contents($runner->getPath('src/Controller/RegistrationController.php'));
                self::assertStringContainsString('EmailVerifier', $controller);
                self::assertStringContainsString('jr@rushlow.dev', $controller);
                self::assertStringContainsString('UniqueEntity', file_get_contents($runner->getPath('src/Entity/User.php')));
            }),
        ];

        yield 'it_rejects_missing_options_non_interactively' => [self::createRegistrationFormTest()
            ->addExtraDependencies('symfonycasts/verify-email-bundle')
            ->run(static function (MakerTestRunner $runner) {
                self::makeUser($runner);

                $invalid = [
                    '--no-interaction --verify-email --redirect-route=app_anonymous' => 'is not a valid email address',
                    '--no-interaction --redirect-route=app_anonymous --authenticator=nope' => 'No authenticator named "nope"',
                ];

                foreach ($invalid as $arguments => $expectedError) {
                    $output = $runner->runMaker([], $arguments, allowedToFail: true);

                    self::assertStringContainsString($expectedError, $output, \sprintf('"%s" was not rejected.', $arguments));
                    self::assertFileDoesNotExist($runner->getPath('src/Controller/RegistrationController.php'));
                }
            }),
        ];

        yield 'it_generates_registration_form_non_interactively_with_auto_login' => [self::createRegistrationFormTest()
            ->run(static function (MakerTestRunner $runner) {
                self::makeUser($runner);

                $runner->modifyYamlFile('config/packages/security.yaml', static function (array $data) {
                    $data['security']['firewalls']['main']['form_login'] = [];

                    return $data;
                });

                $runner->runMaker([], '--no-interaction --auto-login');

                $fixturePath = \dirname(__DIR__, 1).'/fixtures/make-registration-form/expected';

                // --auto-login alone resolves the single configured authenticator,
                // exactly as the prompt does when there is only one to choose from
                self::assertFileEquals($fixturePath.'/RegistrationControllerFormLogin.php', $runner->getPath('src/Controller/RegistrationController.php'));
            }),
        ];

        yield 'it_rejects_ambiguous_auto_login_non_interactively' => [self::createRegistrationFormTest()
            ->run(static function (MakerTestRunner $runner) {
                self::makeUser($runner);

                $runner->modifyYamlFile('config/packages/security.yaml', static function (array $data) {
                    $data['security']['firewalls']['main']['form_login'] = [];
                    $data['security']['firewalls']['main']['custom_authenticator'] = 'App\\Security\\StubAuthenticator';

                    return $data;
                });

                $output = $runner->runMaker([], '--no-interaction --auto-login', allowedToFail: true);

                self::assertStringContainsString('Multiple authenticators are configured', $output);
                self::assertFileDoesNotExist($runner->getPath('src/Controller/RegistrationController.php'));
            }),
        ];

        yield 'it_notes_missing_authenticator_for_auto_login_non_interactively' => [self::createRegistrationFormTest()
            ->run(static function (MakerTestRunner $runner) {
                self::makeUser($runner);

                $output = $runner->runMaker([], '--no-interaction --auto-login --redirect-route=app_anonymous');

                // no authenticator is configured, so --auto-login has nothing to resolve;
                // the command still succeeds, matching the interactive note rather than the prompt's error path
                self::assertStringContainsString('No authenticators found', $output);
                self::assertFileExists($runner->getPath('src/Controller/RegistrationController.php'));
            }),
        ];

        yield 'it_generates_registration_with_entity_and_form_login_with_no_login' => [self::createRegistrationFormTest()
            ->run(static function (MakerTestRunner $runner) {
                self::makeUser($runner);

                $runner->runMaker([
                    // user class guessed,
                    // username field guessed
                    // password guessed
                    '', // yes to add UniqueEntity
                    'n', // verify user
                    // firewall name guessed
                    'n', // yes authenticate after
                    '2', // redirect to app_anonymous after registration
                ]);

                $fixturePath = \dirname(__DIR__, 1).'/fixtures/make-registration-form/expected';

                self::assertFileEquals($fixturePath.'/RegistrationControllerNoLogin.php', $runner->getPath('src/Controller/RegistrationController.php'));

                self::runRegistrationTest($runner, 'it_generates_registration_with_entity_and_authenticator.php');
            }),
        ];

        yield 'it_generates_registration_with_entity_and_form_login_with_security_bundle_login' => [self::createRegistrationFormTest()
            ->run(static function (MakerTestRunner $runner) {
                if (60200 > $runner->getSymfonyVersion()) {
                    self::markTestSkipped('Requires Symfony 6.2+');
                }

                self::makeUser($runner);

                $runner->modifyYamlFile('config/packages/security.yaml', static function (array $data) {
                    $data['security']['firewalls']['main']['form_login']['login_path'] = 'app_login';
                    $data['security']['firewalls']['main']['form_login']['check_path'] = 'app_login';

                    return $data;
                });

                $runner->runMaker([
                    // user class guessed,
                    // username field guessed
                    // password guessed
                    '', // yes to add UniqueEntity
                    'n', // verify user
                    // firewall name guessed
                    '', // yes authenticate after
                    // 1 authenticator will be guessed
                ]);

                $fixturePath = \dirname(__DIR__, 1).'/fixtures/make-registration-form/expected';

                self::assertFileEquals($fixturePath.'/RegistrationControllerFormLogin.php', $runner->getPath('src/Controller/RegistrationController.php'));

                self::runRegistrationTest($runner, 'it_generates_registration_with_entity_and_authenticator.php');
            }),
        ];

        yield 'it_generates_registration_with_entity_and_custom_authenticator' => [self::createRegistrationFormTest()
            ->run(static function (MakerTestRunner $runner) {
                self::makeUser($runner);

                $runner->modifyYamlFile('config/packages/security.yaml', static function (array $data) {
                    $data['security']['firewalls']['main']['custom_authenticator'] = 'App\\Security\\StubAuthenticator';

                    return $data;
                });

                $runner->runMaker([
                    // user class guessed,
                    // username field guessed
                    // password guessed
                    '', // yes to add UniqueEntity
                    'n', // verify user
                    // firewall name guessed
                    '', // yes authenticate after
                    // 1 authenticator will be guessed
                ]);

                $fixturePath = \dirname(__DIR__, 1).'/fixtures/make-registration-form/expected';

                self::assertFileEquals($fixturePath.'/RegistrationControllerCustomAuthenticator.php', $runner->getPath('src/Controller/RegistrationController.php'));

                self::runRegistrationTest($runner, 'it_generates_registration_with_entity_and_authenticator.php');
            }),
        ];

        yield 'it_generates_registration_form_with_no_guessing' => [self::createRegistrationFormTest()
            ->run(static function (MakerTestRunner $runner) {
                self::makeUser($runner, 'emailAlt');

                $runner->runMaker([
                    'App\\Entity\\User',
                    'emailAlt', // username field
                    'passwordAlt', // password field
                    'n', // no UniqueEntity
                    'n', // no verify user
                    '', // yes authenticate after
                    'main', // firewall
                    '1', // authenticator
                ]);
            }),
        ];

        yield 'it_generates_registration_form_with_entity_no_login' => [self::createRegistrationFormTest()
            ->run(static function (MakerTestRunner $runner) {
                self::makeUser($runner);

                $runner->runMaker([
                    // all basic data guessed
                    'y', // add UniqueEntity
                    'n', // no verify user
                    'n', // no authenticate after
                    'app_anonymous', // route name to redirect to
                ]);

                self::runRegistrationTest($runner, 'it_generates_registration_with_entity_and_authenticator.php');
            }),
        ];

        yield 'it_generates_registration_form_with_verification' => [self::createRegistrationFormTest()
            ->addExtraDependencies('symfonycasts/verify-email-bundle')
            // needed for internal functional test
            ->addExtraDependencies('symfony/web-profiler-bundle', 'mailer')
            ->run(static function (MakerTestRunner $runner) {
                $runner->writeFile(
                    'config/packages/mailer.yaml',
                    Yaml::dump(['framework' => [
                        'mailer' => ['dsn' => 'null://null'],
                    ]])
                );

                self::makeUser($runner);

                $output = $runner->runMaker([
                    'n', // add UniqueEntity
                    'y', // verify user
                    'y', // require authentication to verify user email
                    'jr@rushlow.dev', // from email address
                    'SymfonyCasts', // From Name
                    'n', // no authenticate after
                    'app_anonymous', // route number to redirect to
                ]);

                self::assertStringContainsString('Success', $output);

                $generatedFiles = [
                    'src/Security/EmailVerifier.php',
                    'templates/registration/confirmation_email.html.twig',
                ];

                foreach ($generatedFiles as $file) {
                    self::assertFileExists($runner->getPath($file));
                }

                $userContents = file_get_contents($runner->getPath('src/Entity/User.php'));

                self::assertStringContainsString('private bool $isVerified = false', $userContents);

                self::runRegistrationTest($runner, 'it_generates_registration_form_with_verification.php');
            }),
        ];

        yield 'it_generates_registration_form_with_verification_and_translator' => [self::createRegistrationFormTest()
            ->addExtraDependencies('symfonycasts/verify-email-bundle')
            // needed for internal functional test
            ->addExtraDependencies('symfony/web-profiler-bundle', 'mailer', 'symfony/translation')
            ->run(static function (MakerTestRunner $runner) {
                $runner->writeFile(
                    'config/packages/mailer.yaml',
                    Yaml::dump(['framework' => [
                        'mailer' => ['dsn' => 'null://null'],
                    ]])
                );

                self::makeUser($runner);

                $output = $runner->runMaker([
                    'n', // add UniqueEntity
                    'y', // verify user
                    'y', // require authentication to verify user email
                    'victor@symfonycasts.com', // from email address
                    'SymfonyCasts', // From Name
                    'n', // no authenticate after
                    'app_anonymous', // route number to redirect to
                ]);

                self::assertStringContainsString('Success', $output);

                self::runRegistrationTest($runner, 'it_generates_registration_form_with_verification.php');
            }),
        ];

        yield 'it_generates_registration_form_with_tests' => [self::createRegistrationFormTest()
            ->run(static function (MakerTestRunner $runner) {
                self::makeUser($runner);

                $output = $runner->runMaker([
                    'n', // add UniqueEntity
                    'n', // verify user
                    'n', // automatically authenticate after registration
                    'app_anonymous', // route number to redirect to
                    'y', // Generate tests
                ]);

                self::assertStringContainsString('Success', $output);
                self::assertFileExists($runner->getPath('tests/RegistrationControllerTest.php'));

                $runner->configureDatabase();
                $runner->runTests();
            }),
        ];

        yield 'it_generates_registration_form_with_tests_using_flag' => [self::createRegistrationFormTest()
            ->run(static function (MakerTestRunner $runner) {
                self::makeUser($runner);

                $output = $runner->runMaker([
                    'n', // add UniqueEntity
                    'n', // verify user
                    'n', // automatically authenticate after registration
                    'app_anonymous', // route number to redirect to
                ], '--with-tests');

                self::assertStringContainsString('Success', $output);
                self::assertFileExists($runner->getPath('tests/RegistrationControllerTest.php'));

                $runner->configureDatabase();
                $runner->runTests();
            }),
        ];

        yield 'it_generates_registration_form_with_verification_and_with_tests' => [self::createRegistrationFormTest()
            ->addExtraDependencies('symfonycasts/verify-email-bundle')
            // needed for internal functional test
            ->addExtraDependencies('symfony/web-profiler-bundle', 'mailer')
            ->run(static function (MakerTestRunner $runner) {
                $runner->writeFile(
                    'config/packages/mailer.yaml',
                    Yaml::dump(['framework' => [
                        'mailer' => ['dsn' => 'null://null'],
                    ]])
                );

                self::makeUser($runner);

                $output = $runner->runMaker([
                    'n', // add UniqueEntity
                    'y', // verify user
                    'y', // require authentication to verify user email
                    'jr@rushlow.dev', // from email address
                    'SymfonyCasts', // From Name
                    'n', // no authenticate after
                    'app_anonymous', // route number to redirect to
                    'y', // Generate tests
                ]);

                self::assertStringContainsString('Success', $output);

                $generatedFiles = [
                    'src/Security/EmailVerifier.php',
                    'templates/registration/confirmation_email.html.twig',
                    'tests/RegistrationControllerTest.php',
                ];

                foreach ($generatedFiles as $file) {
                    self::assertFileExists($runner->getPath($file));
                }

                $runner->runConsole('cache:clear', [], '--env=test');

                $runner->configureDatabase();
                $runner->runTests();
            }),
        ];
    }

    private static function makeUser(MakerTestRunner $runner, string $identifier = 'email'): void
    {
        $runner->runConsole('make:user', [
            'User', // class name
            'y', // entity
            $identifier, // identifier
            'y', // password
        ]);
    }

    private static function runRegistrationTest(MakerTestRunner $runner, string $filename): void
    {
        $runner->copy(
            'make-registration-form/tests/'.$filename,
            'tests/RegistrationFormTest.php'
        );

        // workaround for a strange behavior where, every other
        // test run, the UniqueEntity would not be seen, because
        // the validation cache was out of date. The cause
        // is currently unknown, so this workaround was added
        $runner->runConsole('cache:clear', [], '--env=test');

        $runner->configureDatabase();
        $runner->runTests();
    }
}
