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
            // @legacy - drop skipped versions when PHP 8.1 is no longer supported.
            ->setSkippedPhpVersions(80100, 80109)
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
                    'src/Form/ChangePasswordForm.php',
                    'src/Form/ResetPasswordRequestForm.php',
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

                $runner->writeFile(
                    'config/packages/mailer.yaml',
                    Yaml::dump(['framework' => [
                        'mailer' => ['dsn' => 'null://null'],
                    ]])
                );

                $runner->copy(
                    'make-reset-password/tests/it_generates_with_normal_setup.php',
                    'tests/ResetPasswordFunctionalTest.php'
                );

                $runner->runTests();
            }),
        ];

        yield 'it_generates_tests' => [$this->createMakerTest()
            // Needed to assertEmails && NotCompromisedPassword
            ->addExtraDependencies('symfony/mailer', 'symfony/http-client')
            // @legacy - drop skipped versions when PHP 8.1 is no longer supported.
            ->setSkippedPhpVersions(80100, 80109)
            ->preRun(function (MakerTestRunner $runner) {
                $runner->copy(
                    'make-reset-password/src/Controller/FixtureController.php',
                    'src/Controller/FixtureController.php'
                );
            })
            ->run(function (MakerTestRunner $runner) {
                $this->makeUser($runner);

                $output = $runner->runMaker([
                    'app_home',
                    'jr@rushlow.dev',
                    'SymfonyCasts',
                    'y',
                ]);

                $this->assertStringContainsString('Success', $output);

                $generatedFiles = [
                    'tests/ResetPasswordControllerTest.php',
                ];

                foreach ($generatedFiles as $file) {
                    $this->assertFileExists($runner->getPath($file));
                }

                $runner->writeFile(
                    'config/packages/mailer.yaml',
                    Yaml::dump(['framework' => [
                        'mailer' => ['dsn' => 'null://null'],
                    ]])
                );

                $runner->copy(
                    'make-reset-password/tests/it_generates_with_normal_setup.php',
                    'tests/ResetPasswordFunctionalTest.php'
                );

                $runner->configureDatabase();
                $runner->runTests();
            }),
        ];

        yield 'it_generates_with_uuid' => [$this->createMakerTest()
            ->setSkippedPhpVersions(80100, 80109)
            ->addExtraDependencies('symfony/uid')
            ->run(function (MakerTestRunner $runner) {
                $this->makeUser($runner);

                $output = $runner->runMaker([
                    'App\Entity\User',
                    'app_home',
                    'jr@rushlow.dev',
                    'SymfonyCasts',
                ], '--with-uuid');

                $this->assertStringContainsString('Success', $output);

                $generatedFiles = [
                    'src/Controller/ResetPasswordController.php',
                    'src/Entity/ResetPasswordRequest.php',
                    'src/Form/ChangePasswordForm.php',
                    'src/Form/ResetPasswordRequestForm.php',
                    'src/Repository/ResetPasswordRequestRepository.php',
                    'templates/reset_password/check_email.html.twig',
                    'templates/reset_password/email.html.twig',
                    'templates/reset_password/request.html.twig',
                    'templates/reset_password/reset.html.twig',
                ];

                foreach ($generatedFiles as $file) {
                    $this->assertFileExists($runner->getPath($file));
                }

                $resetPasswordRequestEntityContents = file_get_contents($runner->getPath('src/Entity/ResetPasswordRequest.php'));
                $this->assertStringContainsString('use Symfony\Component\Uid\Uuid;', $resetPasswordRequestEntityContents);
                $this->assertStringContainsString('[ORM\CustomIdGenerator(class: \'doctrine.uuid_generator\')]', $resetPasswordRequestEntityContents);

                $configFileContents = file_get_contents($runner->getPath('config/packages/reset_password.yaml'));

                // Flex recipe adds comments in reset_password.yaml, check file was replaced by maker
                $this->assertStringNotContainsString('#', $configFileContents);

                $resetPasswordConfig = $runner->readYaml('config/packages/reset_password.yaml');

                $this->assertSame('App\Repository\ResetPasswordRequestRepository', $resetPasswordConfig['symfonycasts_reset_password']['request_password_repository']);

                $runner->writeFile(
                    'config/packages/mailer.yaml',
                    Yaml::dump(['framework' => [
                        'mailer' => ['dsn' => 'null://null'],
                    ]])
                );

                $runner->copy(
                    'make-reset-password/tests/it_generates_with_normal_setup.php',
                    'tests/ResetPasswordFunctionalTest.php'
                );

                $runner->runTests();
            }),
        ];

        yield 'it_generates_with_ulid' => [$this->createMakerTest()
            ->setSkippedPhpVersions(80100, 80109)
            ->addExtraDependencies('symfony/uid')
            ->run(function (MakerTestRunner $runner) {
                $this->makeUser($runner);

                $output = $runner->runMaker([
                    'App\Entity\User',
                    'app_home',
                    'jr@rushlow.dev',
                    'SymfonyCasts',
                ], '--with-ulid');

                $this->assertStringContainsString('Success', $output);

                $generatedFiles = [
                    'src/Controller/ResetPasswordController.php',
                    'src/Entity/ResetPasswordRequest.php',
                    'src/Form/ChangePasswordForm.php',
                    'src/Form/ResetPasswordRequestForm.php',
                    'src/Repository/ResetPasswordRequestRepository.php',
                    'templates/reset_password/check_email.html.twig',
                    'templates/reset_password/email.html.twig',
                    'templates/reset_password/request.html.twig',
                    'templates/reset_password/reset.html.twig',
                ];

                foreach ($generatedFiles as $file) {
                    $this->assertFileExists($runner->getPath($file));
                }

                $resetPasswordRequestEntityContents = file_get_contents($runner->getPath('src/Entity/ResetPasswordRequest.php'));
                $this->assertStringContainsString('use Symfony\Component\Uid\Ulid;', $resetPasswordRequestEntityContents);
                $this->assertStringContainsString('[ORM\CustomIdGenerator(class: \'doctrine.ulid_generator\')]', $resetPasswordRequestEntityContents);

                $configFileContents = file_get_contents($runner->getPath('config/packages/reset_password.yaml'));

                // Flex recipe adds comments in reset_password.yaml, check file was replaced by maker
                $this->assertStringNotContainsString('#', $configFileContents);

                $resetPasswordConfig = $runner->readYaml('config/packages/reset_password.yaml');

                $this->assertSame('App\Repository\ResetPasswordRequestRepository', $resetPasswordConfig['symfonycasts_reset_password']['request_password_repository']);

                $runner->writeFile(
                    'config/packages/mailer.yaml',
                    Yaml::dump(['framework' => [
                        'mailer' => ['dsn' => 'null://null'],
                    ]])
                );

                $runner->copy(
                    'make-reset-password/tests/it_generates_with_normal_setup.php',
                    'tests/ResetPasswordFunctionalTest.php'
                );

                $runner->runTests();
            }),
        ];

        yield 'it_generates_with_translator_installed' => [$this->createMakerTest()
            // @legacy - drop skipped versions when PHP 8.1 is no longer supported.
            ->setSkippedPhpVersions(80100, 80109)
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
            // @legacy - drop skipped versions when PHP 8.1 is no longer supported.
            ->setSkippedPhpVersions(80100, 80109)
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

                $this->assertFileDoesNotExist($runner->getPath('config/packages/reset_password.yaml'));
                $this->assertStringContainsString(
                    'Just remember to set the request_password_repository in your configuration.',
                    $output
                );
            }),
        ];

        yield 'it_amends_configuration' => [$this->createMakerTest()
            // @legacy - drop skipped versions when PHP 8.1 is no longer supported.
            ->setSkippedPhpVersions(80100, 80109)
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
            // @legacy - drop skipped versions when PHP 8.1 is no longer supported.
            ->setSkippedPhpVersions(80100, 80109)
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
                $this->assertStringContainsString('$user->setMyPassword($passwordHasher->hashPassword($user, $plainPassword));', $contentResetPasswordController);
                $this->assertStringContainsString('->to((string) $user->getEmailAddress())', $contentResetPasswordController);

                // check ResetPasswordRequest
                $contentResetPasswordRequest = file_get_contents($runner->getPath('src/Entity/ResetPasswordRequest.php'));

                $this->assertStringContainsString('ORM\ManyToOne', $contentResetPasswordRequest);

                // check ResetPasswordRequestFormType
                $contentResetPasswordRequestFormType = file_get_contents($runner->getPath('/src/Form/ResetPasswordRequestForm.php'));
                $this->assertStringContainsString('->add(\'emailAddress\', EmailType::class, [', $contentResetPasswordRequestFormType);
                // check request.html.twig
                $contentRequestHtml = file_get_contents($runner->getPath('templates/reset_password/request.html.twig'));
                $this->assertStringContainsString('{{ form_row(requestForm.emailAddress) }}', $contentRequestHtml);
            }),
        ];
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
}
