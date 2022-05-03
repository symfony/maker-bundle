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

use Doctrine\ORM\Mapping\Driver\AttributeReader;
use Symfony\Bundle\MakerBundle\Maker\MakeResetPassword;
use Symfony\Bundle\MakerBundle\Test\MakerTestCase;
use Symfony\Bundle\MakerBundle\Test\MakerTestRunner;
use Symfony\Bundle\MakerBundle\Util\ClassSourceManipulator;
use Symfony\Component\Yaml\Yaml;

class MakeResetPasswordTest extends MakerTestCase
{
    protected function getMakerClass(): string
    {
        return MakeResetPassword::class;
    }

    public function getTestDetails(): \Generator
    {
        yield 'it_generates_with_normal_setup' => [$this->createMakerTest()
            ->run(function (MakerTestRunner $runner) {
                $this->makeUser($runner);

                $output = $runner->runMaker([
                    'App\Entity\User',
                    'app_home',
                    'jr@rushlow.dev',
                    'SymfonyCasts',
                ]);

                $this->assertStringContainsString('Success', $output);

                $generatedFiles = [
                    'src/Controller/ResetPasswordController.php',
                    'src/Entity/ResetPasswordRequest.php',
                    'src/Form/ChangePasswordFormType.php',
                    'src/Form/ResetPasswordRequestFormType.php',
                    'src/Repository/ResetPasswordRequestRepository.php',
                    'templates/reset_password/check_email.html.twig',
                    'templates/reset_password/email.html.twig',
                    'templates/reset_password/request.html.twig',
                    'templates/reset_password/reset.html.twig',
                ];

                foreach ($generatedFiles as $file) {
                    $this->assertFileExists($runner->getPath($file));
                }

                $configFileContents = file_get_contents($runner->getPath('config/packages/reset_password.yaml'));

                // Flex recipe adds comments in reset_password.yaml, check file was replaced by maker
                $this->assertStringNotContainsString('#', $configFileContents);

                $resetPasswordConfig = $runner->readYaml('config/packages/reset_password.yaml');

                $this->assertSame('App\Repository\ResetPasswordRequestRepository', $resetPasswordConfig['symfonycasts_reset_password']['request_password_repository']);

                $this->runResetPasswordTest($runner, 'it_generates_with_normal_setup.php');
            }),
        ];

        yield 'it_generates_with_translator_installed' => [$this->createMakerTest()
            ->addExtraDependencies('symfony/translation')
            ->run(function (MakerTestRunner $runner) {
                $this->makeUser($runner);

                $output = $runner->runMaker([
                    'App\Entity\User',
                    'app_home',
                    'victor@symfonycasts.com',
                    'SymfonyCasts',
                ]);

                $this->assertStringContainsString('Success', $output);
            }),
        ];

        yield 'it_generates_with_custom_config' => [$this->createMakerTest()
            ->run(function (MakerTestRunner $runner) {
                $runner->deleteFile('config/packages/reset_password.yaml');
                $runner->writeFile(
                    'config/packages/custom_reset_password.yaml',
                    Yaml::dump(['symfonycasts_reset_password' => [
                        'request_password_repository' => 'symfonycasts.reset_password.fake_request_repository',
                    ]])
                );

                $this->makeUser($runner);

                $output = $runner->runMaker([
                    'App\Entity\User',
                    'app_home',
                    'jr@rushlow.dev',
                    'SymfonyCasts',
                ]);

                $this->assertStringContainsString('Success', $output);

                if (method_exists($this, 'assertFileDoesNotExist')) {
                    $this->assertFileDoesNotExist($runner->getPath('config/packages/reset_password.yaml'));
                } else {
                    $this->assertFileNotExists($runner->getPath('config/packages/reset_password.yaml'));
                }
                $this->assertStringContainsString(
                    'Just remember to set the request_password_repository in your configuration.',
                    $output
                );
            }),
        ];

        yield 'it_amends_configuration' => [$this->createMakerTest()
            ->run(function (MakerTestRunner $runner) {
                $runner->modifyYamlFile('config/packages/reset_password.yaml', function (array $config) {
                    $config['symfonycasts_reset_password']['lifetime'] = 9999;

                    return $config;
                });

                $this->makeUser($runner);

                $output = $runner->runMaker([
                    'App\Entity\User',
                    'app_home',
                    'jr@rushlow.dev',
                    'SymfonyCasts',
                ]);

                $this->assertStringContainsString('Success', $output);

                $resetPasswordConfig = $runner->readYaml('config/packages/reset_password.yaml');

                $this->assertStringContainsString('9999', $resetPasswordConfig['symfonycasts_reset_password']['lifetime']);
                $this->assertSame('App\Repository\ResetPasswordRequestRepository', $resetPasswordConfig['symfonycasts_reset_password']['request_password_repository']);
            }),
        ];

        yield 'it_generates_with_custom_user' => [$this->createMakerTest()
            ->run(function (MakerTestRunner $runner) {
                $this->makeUser($runner, 'emailAddress', 'UserCustom', false);

                $runner->manipulateClass('src/Entity/UserCustom.php', function (ClassSourceManipulator $manipulator) {
                    $manipulator->addSetter('myPassword', 'string', true);
                });

                $output = $runner->runMaker([
                    'App\Entity\UserCustom',
                    'emailAddress',
                    'setMyPassword',
                    'app_home',
                    'jr@rushlow.dev',
                    'SymfonyCasts',
                ]);

                $this->assertStringContainsString('Success', $output);

                // check ResetPasswordController
                $contentResetPasswordController = file_get_contents($runner->getPath('src/Controller/ResetPasswordController.php'));
                $this->assertStringContainsString('$form->get(\'emailAddress\')->getData()', $contentResetPasswordController);
                $this->assertStringContainsString('\'emailAddress\' => $emailFormData,', $contentResetPasswordController);
                $this->assertStringContainsString('$user->setMyPassword($encodedPassword);', $contentResetPasswordController);
                $this->assertStringContainsString('->to($user->getEmailAddress())', $contentResetPasswordController);

                // check ResetPasswordRequest
                $contentResetPasswordRequest = file_get_contents($runner->getPath('src/Entity/ResetPasswordRequest.php'));

                /* @legacy Drop annotation test when annotations are no longer supported. */
                if ($this->useAttributes($runner)) {
                    $this->assertStringContainsString('ORM\ManyToOne(targetEntity: UserCustom::class)', $contentResetPasswordRequest);
                } else {
                    $this->assertStringContainsString('ORM\ManyToOne(targetEntity=UserCustom::class)', $contentResetPasswordRequest);
                }

                // check ResetPasswordRequestFormType
                $contentResetPasswordRequestFormType = file_get_contents($runner->getPath('/src/Form/ResetPasswordRequestFormType.php'));
                $this->assertStringContainsString('->add(\'emailAddress\', EmailType::class, [', $contentResetPasswordRequestFormType);
                // check request.html.twig
                $contentRequestHtml = file_get_contents($runner->getPath('templates/reset_password/request.html.twig'));
                $this->assertStringContainsString('{{ form_row(requestForm.emailAddress) }}', $contentRequestHtml);
            }),
        ];
    }

    private function runResetPasswordTest(MakerTestRunner $runner, string $filename): void
    {
        $runner->writeFile(
            'config/packages/mailer.yaml',
            Yaml::dump(['framework' => [
                'mailer' => ['dsn' => 'null://null'],
            ]])
        );

        $runner->copy(
            'make-reset-password/tests/'.$filename,
            'tests/ResetPasswordFunctionalTest.php'
        );

        $runner->runTests();
    }

    private function makeUser(MakerTestRunner $runner, string $identifier = 'email', string $userClass = 'User', bool $checkPassword = true): void
    {
        $runner->runConsole('make:user', [
            $userClass, // class name
            'y', // entity
            $identifier, // identifier
            $checkPassword ? 'y' : 'n', // password
        ]);
    }

    private function useAttributes(MakerTestRunner $runner): bool
    {
        return \PHP_VERSION_ID >= 80000
            && $runner->doesClassExist(AttributeReader::class)
            && $runner->getSymfonyVersion() >= 50200;
    }
}
