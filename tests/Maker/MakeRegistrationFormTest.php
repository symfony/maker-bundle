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

    private function createRegistrationFormTest(): MakerTestDetails
    {
        return $this->createMakerTest()
            ->preRun(function (MakerTestRunner $runner) {
                $runner->copy(
                    'make-registration-form/standard_setup',
                    ''
                );
            })
        ;
    }

    public function getTestDetails(): \Generator
    {
        yield 'it_generates_registration_with_entity_and_form_login_with_no_login' => [$this->createRegistrationFormTest()
            ->run(function (MakerTestRunner $runner) {
                $this->makeUser($runner);

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

                $this->assertFileEquals($fixturePath.'/RegistrationControllerNoLogin.php', $runner->getPath('src/Controller/RegistrationController.php'));

                $this->runRegistrationTest($runner, 'it_generates_registration_with_entity_and_authenticator.php');
            }),
        ];

        yield 'it_generates_registration_with_entity_and_form_login_with_security_bundle_login' => [$this->createRegistrationFormTest()
            ->run(function (MakerTestRunner $runner) {
                if (60200 > $runner->getSymfonyVersion()) {
                    $this->markTestSkipped('Requires Symfony 6.2+');
                }

                $this->makeUser($runner);

                $runner->modifyYamlFile('config/packages/security.yaml', function (array $data) {
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

                $this->assertFileEquals($fixturePath.'/RegistrationControllerFormLogin.php', $runner->getPath('src/Controller/RegistrationController.php'));

                $this->runRegistrationTest($runner, 'it_generates_registration_with_entity_and_authenticator.php');
            }),
        ];

        yield 'it_generates_registration_with_entity_and_custom_authenticator' => [$this->createRegistrationFormTest()
            ->run(function (MakerTestRunner $runner) {
                $this->makeUser($runner);

                $runner->modifyYamlFile('config/packages/security.yaml', function (array $data) {
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

                $this->assertFileEquals($fixturePath.'/RegistrationControllerCustomAuthenticator.php', $runner->getPath('src/Controller/RegistrationController.php'));

                $this->runRegistrationTest($runner, 'it_generates_registration_with_entity_and_authenticator.php');
            }),
        ];

        yield 'it_generates_registration_form_with_no_guessing' => [$this->createRegistrationFormTest()
            ->run(function (MakerTestRunner $runner) {
                $this->makeUser($runner, 'emailAlt');

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

        yield 'it_generates_registration_form_with_entity_no_login' => [$this->createRegistrationFormTest()
            ->run(function (MakerTestRunner $runner) {
                $this->makeUser($runner);

                $runner->runMaker([
                    // all basic data guessed
                    'y', // add UniqueEntity
                    'n', // no verify user
                    'n', // no authenticate after
                    'app_anonymous', // route name to redirect to
                ]);

                $this->runRegistrationTest($runner, 'it_generates_registration_with_entity_and_authenticator.php');
            }),
        ];

        yield 'it_generates_registration_form_with_verification' => [$this->createRegistrationFormTest()
            ->addExtraDependencies('symfonycasts/verify-email-bundle')
            // needed for internal functional test
            ->addExtraDependencies('symfony/web-profiler-bundle', 'mailer')
            ->run(function (MakerTestRunner $runner) {
                $runner->writeFile(
                    'config/packages/mailer.yaml',
                    Yaml::dump(['framework' => [
                        'mailer' => ['dsn' => 'null://null'],
                    ]])
                );

                $this->makeUser($runner);

                $output = $runner->runMaker([
                    'n', // add UniqueEntity
                    'y', // verify user
                    'y', // require authentication to verify user email
                    'jr@rushlow.dev', // from email address
                    'SymfonyCasts', // From Name
                    'n', // no authenticate after
                    'app_anonymous', // route number to redirect to
                ]);

                $this->assertStringContainsString('Success', $output);

                $generatedFiles = [
                    'src/Security/EmailVerifier.php',
                    'templates/registration/confirmation_email.html.twig',
                ];

                foreach ($generatedFiles as $file) {
                    $this->assertFileExists($runner->getPath($file));
                }

                $userContents = file_get_contents($runner->getPath('src/Entity/User.php'));

                $this->assertStringContainsString('private bool $isVerified = false', $userContents);

                $this->runRegistrationTest($runner, 'it_generates_registration_form_with_verification.php');
            }),
        ];

        yield 'it_generates_registration_form_with_verification_and_translator' => [$this->createRegistrationFormTest()
            ->addExtraDependencies('symfonycasts/verify-email-bundle')
            // needed for internal functional test
            ->addExtraDependencies('symfony/web-profiler-bundle', 'mailer', 'symfony/translation')
            ->run(function (MakerTestRunner $runner) {
                $runner->writeFile(
                    'config/packages/mailer.yaml',
                    Yaml::dump(['framework' => [
                        'mailer' => ['dsn' => 'null://null'],
                    ]])
                );

                $this->makeUser($runner);

                $output = $runner->runMaker([
                    'n', // add UniqueEntity
                    'y', // verify user
                    'y', // require authentication to verify user email
                    'victor@symfonycasts.com', // from email address
                    'SymfonyCasts', // From Name
                    'n', // no authenticate after
                    'app_anonymous', // route number to redirect to
                ]);

                $this->assertStringContainsString('Success', $output);

                $this->runRegistrationTest($runner, 'it_generates_registration_form_with_verification.php');
            }),
        ];

        yield 'it_generates_registration_form_with_tests' => [$this->createRegistrationFormTest()
            ->run(function (MakerTestRunner $runner) {
                $this->makeUser($runner);

                $output = $runner->runMaker([
                    'n', // add UniqueEntity
                    'n', // verify user
                    'n', // automatically authenticate after registration
                    'app_anonymous', // route number to redirect to
                    'y', // Generate tests
                ]);

                $this->assertStringContainsString('Success', $output);
                $this->assertFileExists($runner->getPath('tests/RegistrationControllerTest.php'));

                $runner->configureDatabase();
                $runner->runTests();
            }),
        ];

        yield 'it_generates_registration_form_with_tests_using_flag' => [$this->createRegistrationFormTest()
            ->run(function (MakerTestRunner $runner) {
                $this->makeUser($runner);

                $output = $runner->runMaker([
                    'n', // add UniqueEntity
                    'n', // verify user
                    'n', // automatically authenticate after registration
                    'app_anonymous', // route number to redirect to
                ], '--with-tests');

                $this->assertStringContainsString('Success', $output);
                $this->assertFileExists($runner->getPath('tests/RegistrationControllerTest.php'));

                $runner->configureDatabase();
                $runner->runTests();
            }),
        ];

        yield 'it_generates_registration_form_with_verification_and_with_tests' => [$this->createRegistrationFormTest()
            ->addExtraDependencies('symfonycasts/verify-email-bundle')
            // needed for internal functional test
            ->addExtraDependencies('symfony/web-profiler-bundle', 'mailer')
            ->run(function (MakerTestRunner $runner) {
                $runner->writeFile(
                    'config/packages/mailer.yaml',
                    Yaml::dump(['framework' => [
                        'mailer' => ['dsn' => 'null://null'],
                    ]])
                );

                $this->makeUser($runner);

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

                $this->assertStringContainsString('Success', $output);

                $generatedFiles = [
                    'src/Security/EmailVerifier.php',
                    'templates/registration/confirmation_email.html.twig',
                    'tests/RegistrationControllerTest.php',
                ];

                foreach ($generatedFiles as $file) {
                    $this->assertFileExists($runner->getPath($file));
                }

                $runner->runConsole('cache:clear', [], '--env=test');

                $runner->configureDatabase();
                $runner->runTests();
            }),
        ];
    }

    private function makeUser(MakerTestRunner $runner, string $identifier = 'email'): void
    {
        $runner->runConsole('make:user', [
            'User', // class name
            'y', // entity
            $identifier, // identifier
            'y', // password
        ]);
    }

    private function runRegistrationTest(MakerTestRunner $runner, string $filename): void
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
