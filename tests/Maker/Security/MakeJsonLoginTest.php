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

use Symfony\Bundle\MakerBundle\Maker\Security\MakeJsonLogin;
use Symfony\Bundle\MakerBundle\Test\MakerTestRunner;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

/**
 * @author Jesse Rushlow <jr@rushlow.dev>
 */
final class MakeJsonLoginTest extends AbstractSecurityMakerTestCase
{
    protected function getMakerClass(): string
    {
        return MakeJsonLogin::class;
    }

    public function getTestDetails(): \Generator
    {
        yield 'generates_json_login_using_defaults' => [$this->createMakerTest()
            ->run(function (MakerTestRunner $runner) {
                $this->makeUser($runner);

                $output = $runner->runMaker([
                    'SecurityController', // Controller Name
                    'n', // Generate Logout
                ]);

                $this->assertStringContainsString('Success', $output);

                $fixturePath = \dirname(__DIR__, 2).'/fixtures/security/make-json-login/expected';

                $this->assertFileEquals($fixturePath.'/SecurityControllerWithoutLogout.php', $runner->getPath('src/Controller/SecurityController.php'));

                $securityConfig = $runner->readYaml('config/packages/security.yaml');

                $this->assertSame('app_api_login', $securityConfig['security']['firewalls']['main']['json_login']['check_path']);

                $this->runLoginTest($runner);
            }),
        ];

        yield 'generates_json_login_with_logout' => [$this->createMakerTest()
            ->run(function (MakerTestRunner $runner) {
                $this->makeUser($runner);

                $output = $runner->runMaker([
                    'SecurityController', // Controller Name
                    'y', // Generate Logout
                ]);

                $this->assertStringContainsString('Success', $output);

                $fixturePath = \dirname(__DIR__, 2).'/fixtures/security/make-json-login/expected';

                $this->assertFileEquals($fixturePath.'/SecurityController.php', $runner->getPath('src/Controller/SecurityController.php'));

                $securityConfig = $runner->readYaml('config/packages/security.yaml');

                $this->assertSame('app_api_login', $securityConfig['security']['firewalls']['main']['json_login']['check_path']);
            }),
        ];

        yield 'generates_json_login_with_custom_class_name' => [$this->createMakerTest()
            ->run(function (MakerTestRunner $runner) {
                $this->makeUser($runner);

                $output = $runner->runMaker([
                    'LoginController', // Controller Name
                    'y', // Generate Logout
                ]);

                $this->assertStringContainsString('Success', $output);

                $fixturePath = \dirname(__DIR__, 2).'/fixtures/security/make-json-login/expected';

                $this->assertFileEquals($fixturePath.'/LoginController.php', $runner->getPath('src/Controller/LoginController.php'));

                $securityConfig = $runner->readYaml('config/packages/security.yaml');

                $this->assertSame('app_api_login', $securityConfig['security']['firewalls']['main']['json_login']['check_path']);
            }),
        ];

        yield 'generates_json_login_using_existing_form_login_controller' => [$this->createMakerTest()
            ->run(function (MakerTestRunner $runner) {
                $this->makeUser($runner);

                $runner->copy('security/make-json-login/setup/', '');

                $output = $runner->runMaker([
                    'SecurityController', // Controller Name
                    'n', // Generate Logout
                ]);

                $this->assertStringContainsString('Success', $output);

                $fixturePath = \dirname(__DIR__, 2).'/fixtures/security/make-json-login/expected';

                $this->assertFileEquals($fixturePath.'/SecurityControllerWithFormLogin.php', $runner->getPath('src/Controller/SecurityController.php'));

                $securityConfig = $runner->readYaml('config/packages/security.yaml');

                $this->assertSame('app_api_login', $securityConfig['security']['firewalls']['main']['json_login']['check_path']);
            }),
        ];
    }

    private function runLoginTest(MakerTestRunner $runner): void
    {
        $fixturePath = 'security/make-json-login/';

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
