<?php

/*
 * This file is part of the Symfony MakerBundle package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\MakerBundle\Tests\Maker\Security;

use Symfony\Bundle\MakerBundle\Maker\Security\MakeFormLogin;
use Symfony\Bundle\MakerBundle\Test\MakerTestCase;
use Symfony\Bundle\MakerBundle\Test\MakerTestRunner;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

/**
 * @author Jesse Rushlow <jr@rushlow.dev>
 */
class MakeFormLoginTest extends MakerTestCase
{
    protected function getMakerClass(): string
    {
        return MakeFormLogin::class;
    }

    public function getTestDetails(): \Generator
    {
        yield 'generates_form_login_using_defaults' => [$this->createMakerTest()
            ->run(function (MakerTestRunner $runner) {
                $this->makeUser($runner);

                $output = $runner->runMaker([
                    'SecurityController', // Controller Name
                    'y', // Generate Logout
                ]);

                $this->assertStringContainsString('Success', $output);
                $fixturePath = \dirname(__DIR__, 2).'/fixtures/security/make-form-login/expected';

                $this->assertFileEquals($fixturePath.'/SecurityController.php', $runner->getPath('src/Controller/SecurityController.php'));
                $this->assertFileEquals($fixturePath.'/login.html.twig', $runner->getPath('templates/security/login.html.twig'));

                $securityConfig = $runner->readYaml('config/packages/security.yaml');

                $this->assertSame('app_login', $securityConfig['security']['firewalls']['main']['form_login']['login_path']);
                $this->assertSame('app_login', $securityConfig['security']['firewalls']['main']['form_login']['check_path']);
                $this->assertTrue($securityConfig['security']['firewalls']['main']['form_login']['enable_csrf']);
                $this->assertSame('app_logout', $securityConfig['security']['firewalls']['main']['logout']['path']);

                $this->runLoginTest($runner);
            }),
        ];

        yield 'generates_form_login_without_logout' => [$this->createMakerTest()
            ->run(function (MakerTestRunner $runner) {
                $this->makeUser($runner);

                $output = $runner->runMaker([
                    'SecurityController', // Controller Name
                    'n', // Generate Logout
                ]);

                $this->assertStringContainsString('Success', $output);
                $fixturePath = \dirname(__DIR__, 2).'/fixtures/security/make-form-login/expected';

                $this->assertFileEquals($fixturePath.'/SecurityControllerWithoutLogout.php', $runner->getPath('src/Controller/SecurityController.php'));
                $this->assertFileEquals($fixturePath.'/login_no_logout.html.twig', $runner->getPath('templates/security/login.html.twig'));

                $securityConfig = $runner->readYaml('config/packages/security.yaml');

                $this->assertSame('app_login', $securityConfig['security']['firewalls']['main']['form_login']['login_path']);
                $this->assertSame('app_login', $securityConfig['security']['firewalls']['main']['form_login']['check_path']);
                $this->assertFalse(isset($securityConfig['security']['firewalls']['main']['logout']['path']));
            }),
        ];

        yield 'generates_form_login_with_custom_controller_name' => [$this->createMakerTest()
            ->run(function (MakerTestRunner $runner) {
                $this->makeUser($runner);

                $output = $runner->runMaker([
                    'LoginController', // Controller Name
                    'y', // Generate Logout
                ]);

                $this->assertStringContainsString('Success', $output);
                $fixturePath = \dirname(__DIR__, 2).'/fixtures/security/make-form-login/expected';

                $this->assertFileEquals($fixturePath.'/LoginController.php', $runner->getPath('src/Controller/LoginController.php'));
                $this->assertFileEquals($fixturePath.'/login.html.twig', $runner->getPath('templates/login/login.html.twig'));

                $securityConfig = $runner->readYaml('config/packages/security.yaml');

                $this->assertSame('app_login', $securityConfig['security']['firewalls']['main']['form_login']['login_path']);
                $this->assertSame('app_login', $securityConfig['security']['firewalls']['main']['form_login']['check_path']);
                $this->assertSame('app_logout', $securityConfig['security']['firewalls']['main']['logout']['path']);
            }),
        ];

        yield 'generates_form_login_using_defaults_with_test' => [$this->createMakerTest()
            ->run(function (MakerTestRunner $runner) {
                // Make the UserPasswordHasherInterface available in the test
                $runner->renderTemplateFile('security/make-form-login/FixtureController.php', 'src/Controller/FixtureController.php', []);

                $this->makeUser($runner);

                $output = $runner->runMaker([
                    'SecurityController', // Controller Name
                    'y', // Generate Logout,
                    'y', // Generate tests
                ]);

                $this->assertStringContainsString('Success', $output);
                $fixturePath = \dirname(__DIR__, 2).'/fixtures/security/make-form-login/expected';

                $this->assertFileEquals($fixturePath.'/SecurityController.php', $runner->getPath('src/Controller/SecurityController.php'));
                $this->assertFileEquals($fixturePath.'/login.html.twig', $runner->getPath('templates/security/login.html.twig'));

                $securityConfig = $runner->readYaml('config/packages/security.yaml');

                $this->assertSame('app_login', $securityConfig['security']['firewalls']['main']['form_login']['login_path']);
                $this->assertSame('app_login', $securityConfig['security']['firewalls']['main']['form_login']['check_path']);
                $this->assertTrue($securityConfig['security']['firewalls']['main']['form_login']['enable_csrf']);
                $this->assertSame('app_logout', $securityConfig['security']['firewalls']['main']['logout']['path']);

                $runner->configureDatabase();
                $runner->runTests();
            }),
        ];
    }

    private function runLoginTest(MakerTestRunner $runner): void
    {
        $fixturePath = 'security/make-form-login/';

        $runner->renderTemplateFile($fixturePath.'/LoginTest.php', 'tests/LoginTest.php', []);

        // plaintext password: needed for entities, simplifies overall
        $runner->modifyYamlFile('config/packages/security.yaml', function (array $config) {
            if (isset($config['when@test']['security']['password_hashers'])) {
                $config['when@test']['security']['password_hashers'] = [PasswordAuthenticatedUserInterface::class => 'plaintext'];

                return $config;
            }

            return $config;
        });

        $runner->configureDatabase();

        $runner->runTests();
    }

    private function makeUser(MakerTestRunner $runner, string $identifier = 'email'): void
    {
        $runner->runConsole('make:user', [
            'User', // Class Name
            'y', // Create as Entity
            $identifier, // Property used to identify the user,
            'y', // Uses a password
        ]);
    }
}
