<?php

namespace Symfony\Bundle\MakerBundle\Tests\Maker;

use Symfony\Bundle\MakerBundle\Maker\MakeAuthenticator;
use Symfony\Bundle\MakerBundle\Test\MakerTestCase;
use Symfony\Bundle\MakerBundle\Test\MakerTestDetails;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Yaml;

class MakeAuthenticatorTest extends MakerTestCase
{
    public function getTestDetails()
    {
        yield 'auth_empty_one_firewall' => [
            MakerTestDetails::createTest(
                $this->getMakerInstance(MakeAuthenticator::class),
                [
                    // authenticator type => empty-auth
                    0,
                    // authenticator class name
                    'AppCustomAuthenticator',
                ]
            )
            ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeAuthenticator')
            ->assert(
                function (string $output, string $directory) {
                    $this->assertStringContainsString('Success', $output);

                    $fs = new Filesystem();
                    $this->assertTrue($fs->exists(sprintf('%s/src/Security/AppCustomAuthenticator.php', $directory)));

                    $securityConfig = Yaml::parse(file_get_contents(sprintf('%s/config/packages/security.yaml', $directory)));
                    $this->assertEquals(
                        'App\\Security\\AppCustomAuthenticator',
                        $securityConfig['security']['firewalls']['main']['guard']['authenticators'][0]
                    );
                }
            ),
        ];

        yield 'auth_empty_multiple_firewalls' => [
            MakerTestDetails::createTest(
                $this->getMakerInstance(MakeAuthenticator::class),
                [
                    // authenticator type => empty-auth
                    0,
                    // class name
                    'AppCustomAuthenticator',
                    // firewall name
                    1,
                ]
            )
            ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeAuthenticatorMultipleFirewalls')
            ->assert(
                function (string $output, string $directory) {
                    $this->assertStringContainsString('Success', $output);

                    $securityConfig = Yaml::parse(file_get_contents(sprintf('%s/config/packages/security.yaml', $directory)));
                    $this->assertEquals(
                        'App\\Security\\AppCustomAuthenticator',
                        $securityConfig['security']['firewalls']['second']['guard']['authenticators'][0]
                    );
                }
            ),
        ];

        yield 'auth_empty_existing_authenticator' => [
            MakerTestDetails::createTest(
                $this->getMakerInstance(MakeAuthenticator::class),
                [
                    // authenticator type => empty-auth
                    0,
                    // class name
                    'AppCustomAuthenticator',
                    // firewall name
                    1,
                ]
            )
            ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeAuthenticatorExistingAuthenticator')
            ->assert(
                function (string $output, string $directory) {
                    $this->assertStringContainsString('Success', $output);

                    $securityConfig = Yaml::parse(file_get_contents(sprintf('%s/config/packages/security.yaml', $directory)));
                    $this->assertEquals(
                        'App\\Security\\AppCustomAuthenticator',
                        $securityConfig['security']['firewalls']['main']['guard']['entry_point']
                    );
                }
            ),
        ];

        yield 'auth_empty_multiple_firewalls_existing_authenticator' => [
            MakerTestDetails::createTest(
                $this->getMakerInstance(MakeAuthenticator::class),
                [
                    // authenticator type => empty-auth
                    0,
                    // class name
                    'AppCustomAuthenticator',
                    // firewall name
                    1,
                    // entry point
                    1,
                ]
            )
            ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeAuthenticatorMultipleFirewallsExistingAuthenticator')
            ->assert(
                function (string $output, string $directory) {
                    $this->assertStringContainsString('Success', $output);

                    $securityConfig = Yaml::parse(file_get_contents(sprintf('%s/config/packages/security.yaml', $directory)));
                    $this->assertEquals(
                        'App\\Security\\AppCustomAuthenticator',
                        $securityConfig['security']['firewalls']['second']['guard']['entry_point']
                    );
                }
            ),
        ];

        yield 'auth_login_form_user_entity_with_encoder' => [
            MakerTestDetails::createTest(
                $this->getMakerInstance(MakeAuthenticator::class),
                [
                    // authenticator type => login-form
                    1,
                    // class name
                    'AppCustomAuthenticator',
                    // controller name
                    'SecurityController',
                    // field name
                    'userEmail',
                    'no',
                ]
            )
            ->addExtraDependencies('doctrine')
            ->addExtraDependencies('twig')
            ->addExtraDependencies('symfony/form')
            ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeAuthenticatorLoginFormUserEntity')
            ->configureDatabase()
            ->updateSchemaAfterCommand()
            ->assert(
                function (string $output, string $directory) {
                    $this->assertStringContainsString('Success', $output);

                    $fs = new Filesystem();
                    $this->assertTrue($fs->exists(sprintf('%s/src/Controller/SecurityController.php', $directory)));
                    $this->assertTrue($fs->exists(sprintf('%s/templates/security/login.html.twig', $directory)));
                    $this->assertTrue($fs->exists(sprintf('%s/src/Security/AppCustomAuthenticator.php', $directory)));
                }
            ),
        ];

        yield 'auth_login_form_custom_username_field' => [
            MakerTestDetails::createTest(
                $this->getMakerInstance(MakeAuthenticator::class),
                [
                    // authenticator type => login-form
                    1,
                    // class name
                    'AppCustomAuthenticator',
                    // controller name
                    'SecurityController',
                    // user class
                    'App\\Security\\User',
                    // username field => userEmail
                    0,
                    'no',
                ]
            )
            ->addExtraDependencies('doctrine/annotations')
            ->addExtraDependencies('twig')
            ->addExtraDependencies('symfony/form')
            ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeAuthenticatorLoginFormCustomUsernameField'),
        ];

        yield 'auth_login_form_user_entity_no_encoder' => [
            MakerTestDetails::createTest(
                $this->getMakerInstance(MakeAuthenticator::class),
                [
                    // authenticator type => login-form
                    1,
                    // class name
                    'AppCustomAuthenticator',
                    // controller name
                    'SecurityController',
                    'no',
                ]
            )
            ->addExtraDependencies('doctrine')
            ->addExtraDependencies('twig')
            ->addExtraDependencies('symfony/form')
            ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeAuthenticatorLoginFormUserEntityNoEncoder')
            ->configureDatabase()
            ->updateSchemaAfterCommand(),
        ];

        yield 'auth_login_form_user_not_entity_with_encoder' => [
            MakerTestDetails::createTest(
                $this->getMakerInstance(MakeAuthenticator::class),
                [
                    // authenticator type => login-form
                    1,
                    // class name
                    'AppCustomAuthenticator',
                    // controller name
                    'SecurityController',
                    // user class
                    'App\Security\User',
                    'no',
                ]
            )
            ->addExtraDependencies('twig')
            ->addExtraDependencies('doctrine/annotations')
            ->addExtraDependencies('symfony/form')
            ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeAuthenticatorLoginFormUserNotEntity'),
        ];

        yield 'auth_login_form_user_not_entity_no_encoder' => [
            MakerTestDetails::createTest(
                $this->getMakerInstance(MakeAuthenticator::class),
                [
                    // authenticator type => login-form
                    1,
                    // class name
                    'AppCustomAuthenticator',
                    // controller name
                    'SecurityController',
                    // user class
                    'App\Security\User',
                    'no',
                ]
            )
            ->addExtraDependencies('twig')
            ->addExtraDependencies('doctrine/annotations')
            ->addExtraDependencies('symfony/form')
            ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeAuthenticatorLoginFormUserNotEntityNoEncoder'),
        ];

        yield 'auth_login_form_existing_controller' => [
            MakerTestDetails::createTest(
                $this->getMakerInstance(MakeAuthenticator::class),
                [
                    // authenticator type => login-form
                    1,
                    // class name
                    'AppCustomAuthenticator',
                    // controller name
                    'SecurityController',
                    'no',
                ]
            )
            ->addExtraDependencies('doctrine')
            ->addExtraDependencies('twig')
            ->addExtraDependencies('symfony/form')
            ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeAuthenticatorLoginFormExistingController')
            ->configureDatabase()
            ->updateSchemaAfterCommand(),
        ];

        yield 'auth_login_form_user_entity_with_encoder_logout' => [
            MakerTestDetails::createTest(
                $this->getMakerInstance(MakeAuthenticator::class),
                [
                    // authenticator type => login-form
                    1,
                    // class name
                    'AppCustomAuthenticator',
                    // controller name
                    'SecurityController',
                    // logout support
                    'yes',
                ]
            )
                ->addExtraDependencies('doctrine')
                ->addExtraDependencies('twig')
                ->addExtraDependencies('symfony/form')
                ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeAuthenticatorLoginFormUserEntityLogout')
                ->configureDatabase()
                ->updateSchemaAfterCommand()
                ->assert(
                    function (string $output, string $directory) {
                        $this->assertStringContainsString('Success', $output);

                        $fs = new Filesystem();
                        $this->assertTrue($fs->exists(sprintf('%s/src/Controller/SecurityController.php', $directory)));
                        $this->assertTrue($fs->exists(sprintf('%s/templates/security/login.html.twig', $directory)));
                        $this->assertTrue($fs->exists(sprintf('%s/src/Security/AppCustomAuthenticator.php', $directory)));

                        $securityConfig = Yaml::parse(file_get_contents(sprintf('%s/config/packages/security.yaml', $directory)));
                        $this->assertEquals(
                            'app_logout',
                            $securityConfig['security']['firewalls']['main']['logout']['path']
                        );
                    }
                ),
        ];
    }
}
