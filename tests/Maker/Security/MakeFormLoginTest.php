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
use Symfony\Bundle\MakerBundle\Test\MakerTestRunner;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

/**
 * @author Jesse Rushlow <jr@rushlow.dev>
 */
final class MakeFormLoginTest extends AbstractSecurityMakerTestCase
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
    }

    private function runLoginTest(MakerTestRunner $runner): void
    {
        if (60000 > $runner->getSymfonyVersion()) {
            // @legacy - In 5.4 tests, we need to tell Symfony to look for the route attributes in `src/Controller`
            $runner->copy('router-annotations.yaml', 'config/routes/annotations.yaml');
        }

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
}
