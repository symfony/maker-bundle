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

use Symfony\Bundle\MakerBundle\Maker\MakeUser;
use Symfony\Bundle\MakerBundle\Test\MakerTestCase;
use Symfony\Bundle\MakerBundle\Test\MakerTestRunner;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class MakeUserTest extends MakerTestCase
{
    protected function getMakerClass(): string
    {
        return MakeUser::class;
    }

    public function getTestDetails(): \Generator
    {
        yield 'it_generates_entity_with_password' => [$this->createMakerTest()
            ->addExtraDependencies('doctrine')
            ->run(function (MakerTestRunner $runner) {
                $runner->copy(
                    'make-user/standard_setup',
                    ''
                );

                $runner->runMaker([
                    // user class name
                    'User',
                    'y', // entity
                    'email', // identity property
                    'y', // with password
                ]);

                $this->runUserTest($runner, 'it_generates_entity_with_password.php');
            }),
        ];

        yield 'it_generates_entity_with_password_and_uuid' => [$this->createMakerTest()
            ->addExtraDependencies('doctrine')
            ->addExtraDependencies('symfony/uid')
            ->run(function (MakerTestRunner $runner) {
                $runner->copy(
                    'make-user/standard_setup',
                    ''
                );

                $runner->runMaker([
                    // user class name
                    'User',
                    'y', // entity
                    'email', // identity property
                    'y', // with password
                ], '--with-uuid');

                $this->runUserTest($runner, 'it_generates_entity_with_password_and_uuid.php');
            }),
        ];

        yield 'it_generates_entity_with_password_and_ulid' => [$this->createMakerTest()
            ->addExtraDependencies('doctrine')
            ->addExtraDependencies('symfony/uid')
            ->run(function (MakerTestRunner $runner) {
                $runner->copy(
                    'make-user/standard_setup',
                    ''
                );

                $runner->runMaker([
                    // user class name
                    'User',
                    'y', // entity
                    'email', // identity property
                    'y', // with password
                ], '--with-ulid');

                $this->runUserTest($runner, 'it_generates_entity_with_password_and_ulid.php');
            }),
        ];

        yield 'it_generates_non_entity_no_password' => [$this->createMakerTest()
            ->addExtraDependencies('doctrine')
            ->run(function (MakerTestRunner $runner) {
                $runner->copy(
                    'make-user/standard_setup',
                    ''
                );

                $runner->runMaker([
                    // user class name (with non-traditional name)
                    'FunUser',
                    'n', // entity
                    'username', // identity property
                    'n', // login with password?
                ]);

                // simplification: allows us to assume loadUserByIdentifier in test
                $runner->replaceInFile(
                    'src/Security/UserProvider.php',
                    'throw new \Exception(\'TODO: fill in refreshUser() inside \'.__FILE__);',
                    'return $user;'
                );

                $runner->replaceInFile(
                    'src/Security/UserProvider.php',
                    'throw new \Exception(\'TODO: fill in loadUserByIdentifier() inside \'.__FILE__);',
                    'return (new FunUser())->setUsername($identifier);'
                );

                $this->runUserTest($runner, 'it_generates_non_entity_no_password.php');
            }),
        ];
    }

    private function runUserTest(MakerTestRunner $runner, string $filename): void
    {
        $runner->copy(
            'make-user/tests/'.$filename,
            'tests/GeneratedUserTest.php'
        );

        $runner->modifyYamlFile('config/packages/security.yaml', function (array $config) {
            $config['security']['firewalls']['main']['custom_authenticator'] = 'App\Security\AutomaticAuthenticator';

            return $config;
        });

        // make a service accessible in the test
        // (the real one is removed as it's never used in the app)
        $runner->modifyYamlFile('config/services.yaml', function (array $config) {
            $config['services']['test_password_hasher'] = [
                'public' => true,
                'alias' => UserPasswordHasherInterface::class,
            ];

            return $config;
        });

        $runner->configureDatabase();

        $runner->runTests();
    }
}
