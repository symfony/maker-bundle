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
                $runner->adjustAuthenticatorForLegacyPassportInterface('src/Security/StubAuthenticator.php');

                if (60000 > $runner->getSymfonyVersion()) {
                    /*
                     * @Legacy - Drop when Symfony 6.0 is LTS
                     *
                     * This is a round about way to handle empty yaml files and YamlSourceManipulator.
                     * Prior to Symfony 6.0, the routes.yaml was empty w/ a comment line. YSM
                     * requires a top level array structure to manipulate them.
                     */
                    $runner->writeFile('config/routes.yaml', 'app_homepage:');
                }

                $runner->modifyYamlFile('config/routes.yaml', function (array $yaml) {
                    $yaml['app_homepage'] = ['path' => '/', 'controller' => 'App\Controller\TestingController::homepage'];
                    $yaml['app_anonymous'] = ['path' => '/anonymous', 'controller' => 'App\Controller\TestingController::anonymous'];

                    return $yaml;
                });
            })
        ;
    }

    public function getTestDetails()
    {
        yield 'it_generates_registration_with_entity_and_authenticator' => [$this->createRegistrationFormTest()
            ->addRequiredPackageVersion('symfony/security-bundle', '>=5.2')
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

                $this->runRegistrationTest($runner, 'it_generates_registration_with_entity_and_authenticator.php');
            }),
        ];

        yield 'it_generates_registration_with_entity_and_legacy_guard_authenticator' => [$this->createRegistrationFormTest()
            ->addRequiredPackageVersion('symfony/security-bundle', '<5.2')
            ->run(function (MakerTestRunner $runner) {
                $this->makeUser($runner);

                $runner->modifyYamlFile('config/packages/security.yaml', function (array $data) {
                    $data['security']['firewalls']['main']['guard'] = [
                        'authenticators' => ['App\\Security\\LegacyGuardStubAuthenticator'],
                    ];

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
            ->setRequiredPhpVersion(70200)
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
                    0, // route number to redirect to
                ]);

                $this->assertStringContainsString('Success', $output);

                $generatedFiles = [
                    'src/Security/EmailVerifier.php',
                    'templates/registration/confirmation_email.html.twig',
                ];

                foreach ($generatedFiles as $file) {
                    $this->assertFileExists($runner->getPath($file));
                }

                $this->runRegistrationTest($runner, 'it_generates_registration_form_with_verification.php');
            }),
        ];

        yield 'it_generates_registration_form_with_verification_and_translator' => [$this->createRegistrationFormTest()
            ->setRequiredPhpVersion(70200)
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
                    0, // route number to redirect to
                ]);

                $this->assertStringContainsString('Success', $output);

                $this->runRegistrationTest($runner, 'it_generates_registration_form_with_verification.php');
            }),
        ];
    }

    private function makeUser(MakerTestRunner $runner, string $identifier = 'email')
    {
        $runner->runConsole('make:user', [
            'User', // class name
            'y', // entity
            $identifier, // identifier
            'y', // password
        ]);
    }

    private function runRegistrationTest(MakerTestRunner $runner, string $filename)
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
