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

use Symfony\Bundle\MakerBundle\Maker\MakeAuthenticator;
use Symfony\Bundle\MakerBundle\Test\MakerTestCase;
use Symfony\Bundle\MakerBundle\Test\MakerTestRunner;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

/**
 * @group legacy
 */
class MakeAuthenticatorTest extends MakerTestCase
{
    protected function getMakerClass(): string
    {
        return MakeAuthenticator::class;
    }

    public function getTestDetails(): \Generator
    {
        yield 'auth_empty_one_firewall' => [$this->createMakerTest()
            ->run(function (MakerTestRunner $runner) {
                $output = $runner->runMaker([
                    // authenticator type => empty-auth
                    0,
                    // authenticator class name
                    'AppCustomAuthenticator',
                ]);

                $this->assertStringContainsString('Success', $output);
                $this->assertFileExists($runner->getPath('src/Security/AppCustomAuthenticator.php'));
                $securityConfig = $runner->readYaml('config/packages/security.yaml');
                $this->assertEquals(
                    'App\\Security\\AppCustomAuthenticator',
                    $securityConfig['security']['firewalls']['main']['custom_authenticator']
                );
            }),
        ];

        yield 'auth_empty_multiple_firewalls' => [$this->createMakerTest()
            ->run(function (MakerTestRunner $runner) {
                $runner->modifyYamlFile('config/packages/security.yaml', function (array $config) {
                    $config['security']['firewalls']['second']['lazy'] = true;

                    return $config;
                });

                $output = $runner->runMaker([
                    // authenticator type => empty-auth
                    0,
                    // class name
                    'AppCustomAuthenticator',
                    // firewall name (1 will be the "second" firewall)
                    1,
                ]);

                $this->assertStringContainsString('Success', $output);
                $securityConfig = $runner->readYaml('config/packages/security.yaml');
                $this->assertEquals(
                    'App\\Security\\AppCustomAuthenticator',
                    $securityConfig['security']['firewalls']['second']['custom_authenticator']
                );
            }),
        ];

        yield 'auth_empty_existing_authenticator' => [$this->createMakerTest()
            ->run(function (MakerTestRunner $runner) {
                $runner->copy(
                    'make-auth/BlankAuthenticator.php',
                    'src/Security/BlankAuthenticator.php'
                );

                $runner->modifyYamlFile('config/packages/security.yaml', function (array $config) {
                    $config['security']['firewalls']['main']['custom_authenticator'] = 'App\Security\BlankAuthenticator';

                    return $config;
                });

                $output = $runner->runMaker([
                    // authenticator type => empty-auth
                    0,
                    // class name
                    'AppCustomAuthenticator',
                    // firewall name
                    1,
                ]);

                $this->assertStringContainsString('Success', $output);

                $securityConfig = $runner->readYaml('config/packages/security.yaml');
                $this->assertEquals(
                    'App\\Security\\AppCustomAuthenticator',
                    $securityConfig['security']['firewalls']['main']['custom_authenticator'][1]
                );
            }),
        ];

        yield 'auth_empty_multiple_firewalls_existing_authenticator' => [$this->createMakerTest()
            ->run(function (MakerTestRunner $runner) {
                $runner->copy(
                    'make-auth/BlankAuthenticator.php',
                    'src/Security/BlankAuthenticator.php'
                );

                $runner->modifyYamlFile('config/packages/security.yaml', function (array $config) {
                    $config['security']['firewalls']['second'] = ['lazy' => true, 'custom_authenticator' => 'App\Security\BlankAuthenticator'];

                    return $config;
                });

                $output = $runner->runMaker([
                    // authenticator type => empty-auth
                    0,
                    // class name
                    'AppCustomAuthenticator',
                    // firewall name
                    1,
                    // entry point
                    1,
                ]);

                $this->assertStringContainsString('Success', $output);

                $securityConfig = $runner->readYaml('config/packages/security.yaml');
                $this->assertEquals(
                    'App\\Security\\AppCustomAuthenticator',
                    $securityConfig['security']['firewalls']['second']['custom_authenticator'][1]
                );
            }),
        ];

        yield 'auth_login_form_user_entity_with_hasher' => [$this->createMakerTest()
            ->addExtraDependencies('doctrine', 'twig', 'symfony/form')
            ->run(function (MakerTestRunner $runner) {
                $this->makeUser($runner, 'userEmail');

                $output = $runner->runMaker([
                    // authenticator type => login-form
                    1,
                    // class name
                    'AppCustomAuthenticator',
                    // controller name
                    'SecurityController',
                    // field name
                    'userEmail',
                    'no',
                    // remember me support => no
                    'no',
                ]);

                $this->runLoginTest($runner, 'userEmail');

                $this->assertStringContainsString('Success', $output);

                $this->assertFileExists($runner->getPath('src/Controller/SecurityController.php'));
                $this->assertFileExists($runner->getPath('templates/security/login.html.twig'));
                $this->assertFileExists($runner->getPath('src/Security/AppCustomAuthenticator.php'));
            }),
        ];

        yield 'auth_login_form_no_entity_custom_username_field' => [$this->createMakerTest()
            ->addExtraDependencies('twig', 'symfony/form')
            ->run(function (MakerTestRunner $runner) {
                $this->makeUser($runner, 'userEmail', false);

                $runner->runMaker([
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
                    // remember me support => no
                    'no',
                ]);

                $runner->runTests();
                $this->runLoginTest(
                    $runner,
                    'userEmail',
                    false,
                    'App\\Security\\User'
                );
            }),
        ];

        yield 'auth_login_form_user_not_entity_with_hasher' => [$this->createMakerTest()
            ->addExtraDependencies('twig', 'symfony/form')
            ->run(function (MakerTestRunner $runner) {
                $this->makeUser($runner, 'email', false);

                $runner->runMaker([
                    // authenticator type => login-form
                    1,
                    // class name
                    'AppCustomAuthenticator',
                    // controller name
                    'SecurityController',
                    // user class
                    'App\Security\User',
                    'no',
                    // remember me support => no
                    'no',
                ]);
            }),
        ];

        yield 'auth_login_form_existing_controller' => [$this->createMakerTest()
            ->addExtraDependencies('doctrine', 'twig', 'symfony/form')
            ->run(function (MakerTestRunner $runner) {
                $this->makeUser($runner, 'email');

                $runner->copy(
                    'make-auth/SecurityController-empty.php',
                    'src/Controller/SecurityController.php'
                );

                $runner->runMaker([
                    // authenticator type => login-form
                    1,
                    // class name
                    'AppCustomAuthenticator',
                    // controller name
                    'SecurityController',
                    'no',
                    // remember me support => no
                    'no',
                ]);

                $this->runLoginTest($runner, 'email');
            }),
        ];

        yield 'auth_login_form_user_entity_with_logout' => [$this->createMakerTest()
            ->addExtraDependencies('doctrine', 'twig', 'symfony/form')
            ->run(function (MakerTestRunner $runner) {
                $this->makeUser($runner, 'userEmail');

                $output = $runner->runMaker([
                    // authenticator type => login-form
                    1,
                    // class name
                    'AppCustomAuthenticator',
                    // controller name
                    'SecurityController',
                    // logout support
                    'yes',
                    // remember me support => no
                    'no',
                ]);

                $this->runLoginTest($runner, 'userEmail', true, 'App\\Entity\\User', true);

                $this->assertStringContainsString('Success', $output);

                $this->assertFileExists($runner->getPath('src/Controller/SecurityController.php'));
                $this->assertFileExists($runner->getPath('templates/security/login.html.twig'));
                $this->assertFileExists($runner->getPath('src/Security/AppCustomAuthenticator.php'));

                $securityConfig = $runner->readYaml('config/packages/security.yaml');
                $this->assertEquals(
                    'app_logout',
                    $securityConfig['security']['firewalls']['main']['logout']['path']
                );
            }),
        ];

        yield 'auth_login_form_remember_me_via_checkbox' => [$this->createMakerTest()
            ->addExtraDependencies('doctrine', 'twig', 'symfony/form')
            ->run(function (MakerTestRunner $runner) {
                $this->makeUser($runner, 'userEmail');

                $output = $runner->runMaker([
                    // authenticator type => login-form
                    1,
                    // class name
                    'AppCustomAuthenticator',
                    // controller name
                    'SecurityController',
                    // logout support
                    'yes',
                    // remember me support => yes
                    'yes',
                    // remember me type => checkbox
                    0,
                ]);

                $this->runLoginTest($runner, 'userEmail');

                $this->assertStringContainsString('Success', $output);
                $seucrityConfig = $runner->readYaml('config/packages/security.yaml');
                $firewallMain = $seucrityConfig['security']['firewalls']['main'];

                $this->assertEquals('%kernel.secret%', $firewallMain['remember_me']['secret']);
                $this->assertEquals('604800', $firewallMain['remember_me']['lifetime']);
                $this->assertArrayNotHasKey('always_remember_me', $firewallMain['remember_me']);
            }),
        ];

        yield 'auth_login_form_always_remember_me' => [$this->createMakerTest()
            ->addExtraDependencies('doctrine', 'twig', 'symfony/form')
            ->run(function (MakerTestRunner $runner) {
                $this->makeUser($runner, 'userEmail');

                $output = $runner->runMaker([
                    // authenticator type => login-form
                    1,
                    // class name
                    'AppCustomAuthenticator',
                    // controller name
                    'SecurityController',
                    // logout support
                    'yes',
                    // remember me support => yes
                    'yes',
                    // remember me type => always
                    1,
                ]);

                $this->runLoginTest($runner, 'userEmail');

                $this->assertStringContainsString('Success', $output);
                $seucrityConfig = $runner->readYaml('config/packages/security.yaml');
                $firewallMain = $seucrityConfig['security']['firewalls']['main'];

                $this->assertEquals('%kernel.secret%', $firewallMain['remember_me']['secret']);
                $this->assertTrue($firewallMain['remember_me']['always_remember_me']);
            }),
        ];
    }

    private function runLoginTest(MakerTestRunner $runner, string $userIdentifier, bool $isEntity = true, string $userClass = 'App\\Entity\\User', bool $testLogin = false): void
    {
        $runner->renderTemplateFile(
            'make-auth/LoginFlowTest.php.twig',
            'tests/LoginFlowTest.php',
            [
                'userIdentifier' => $userIdentifier,
                'isEntity' => $isEntity,
                'userClass' => $userClass,
                'testLogin' => $testLogin,
            ]
        );

        // plaintext password: needed for entities, simplifies overall
        $runner->modifyYamlFile('config/packages/security.yaml', function (array $config) {
            if (isset($config['when@test']['security']['password_hashers'])) {
                $config['when@test']['security']['password_hashers'] = [PasswordAuthenticatedUserInterface::class => 'plaintext'];

                return $config;
            }

            return $config;
        });

        if ($isEntity) {
            $runner->configureDatabase();
        }
        $runner->runTests();
    }

    private function makeUser(MakerTestRunner $runner, string $userIdentifier, bool $isEntity = true): void
    {
        $runner->runConsole('make:user', [
            'User', // class name
            $isEntity ? 'y' : 'n', // entity
            $userIdentifier, // identifier
            'y', // password
        ]);

        if (!$isEntity) {
            $runner->copy(
                'make-auth/UserProvider-no-entity.php',
                'src/Security/UserProvider.php'
            );
        }
    }
}
