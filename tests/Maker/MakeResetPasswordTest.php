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

    public static function getTestDetails(): \Generator
    {
        yield 'it_generates_with_normal_setup' => [self::buildMakerTest()
            // @legacy - drop skipped versions when PHP 8.1 is no longer supported.
            ->setSkippedPhpVersions(80100, 80109)
            ->run(static function (MakerTestRunner $runner) {
                self::makeUser($runner);

                $output = $runner->runMaker([
                    'app_home',
                    'jr@rushlow.dev',
                    'SymfonyCasts',
                ]);

                self::assertStringContainsString('Success', $output);

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
                    self::assertFileExists($runner->getPath($file));
                }

                $configFileContents = file_get_contents($runner->getPath('config/packages/reset_password.yaml'));

                // Flex recipe adds comments in reset_password.yaml, check file was replaced by maker
                self::assertStringNotContainsString('#', $configFileContents);

                $resetPasswordConfig = $runner->readYaml('config/packages/reset_password.yaml');

                self::assertSame('App\Repository\ResetPasswordRequestRepository', $resetPasswordConfig['symfonycasts_reset_password']['request_password_repository']);

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

        yield 'it_generates_tests' => [self::buildMakerTest()
            // Needed to assertEmails && NotCompromisedPassword
            ->addExtraDependencies('symfony/mailer', 'symfony/http-client')
            // @legacy - drop skipped versions when PHP 8.1 is no longer supported.
            ->setSkippedPhpVersions(80100, 80109)
            ->preRun(static function (MakerTestRunner $runner) {
                $runner->copy(
                    'make-reset-password/src/Controller/FixtureController.php',
                    'src/Controller/FixtureController.php'
                );
            })
            ->run(static function (MakerTestRunner $runner) {
                self::makeUser($runner);

                $output = $runner->runMaker([
                    'app_home',
                    'jr@rushlow.dev',
                    'SymfonyCasts',
                    'y',
                ]);

                self::assertStringContainsString('Success', $output);

                $generatedFiles = [
                    'tests/ResetPasswordControllerTest.php',
                ];

                foreach ($generatedFiles as $file) {
                    self::assertFileExists($runner->getPath($file));
                }

                /*
                 * Move the reset link further to the right in the email body. The
                 * quoted-printable encoder then wraps the line in the middle of the
                 * token, which truncates the link when the raw message is read
                 * instead of the text body.
                 */
                $runner->replaceInFile(
                    'templates/reset_password/email.html.twig',
                    '<a href=',
                    'Reset your password: <a href='
                );

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

        yield 'it_generates_with_uuid' => [self::buildMakerTest()
            ->setSkippedPhpVersions(80100, 80109)
            ->addExtraDependencies('symfony/uid')
            ->run(static function (MakerTestRunner $runner) {
                self::makeUser($runner);

                $output = $runner->runMaker([
                    'app_home',
                    'jr@rushlow.dev',
                    'SymfonyCasts',
                ], '--with-uuid');

                self::assertStringContainsString('Success', $output);

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
                    self::assertFileExists($runner->getPath($file));
                }

                $resetPasswordRequestEntityContents = file_get_contents($runner->getPath('src/Entity/ResetPasswordRequest.php'));
                self::assertStringContainsString('use Symfony\Component\Uid\Uuid;', $resetPasswordRequestEntityContents);
                self::assertStringContainsString('[ORM\CustomIdGenerator(class: \'doctrine.uuid_generator\')]', $resetPasswordRequestEntityContents);

                $configFileContents = file_get_contents($runner->getPath('config/packages/reset_password.yaml'));

                // Flex recipe adds comments in reset_password.yaml, check file was replaced by maker
                self::assertStringNotContainsString('#', $configFileContents);

                $resetPasswordConfig = $runner->readYaml('config/packages/reset_password.yaml');

                self::assertSame('App\Repository\ResetPasswordRequestRepository', $resetPasswordConfig['symfonycasts_reset_password']['request_password_repository']);

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

        yield 'it_generates_with_ulid' => [self::buildMakerTest()
            ->setSkippedPhpVersions(80100, 80109)
            ->addExtraDependencies('symfony/uid')
            ->run(static function (MakerTestRunner $runner) {
                self::makeUser($runner);

                $output = $runner->runMaker([
                    'app_home',
                    'jr@rushlow.dev',
                    'SymfonyCasts',
                ], '--with-ulid');

                self::assertStringContainsString('Success', $output);

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
                    self::assertFileExists($runner->getPath($file));
                }

                $resetPasswordRequestEntityContents = file_get_contents($runner->getPath('src/Entity/ResetPasswordRequest.php'));
                self::assertStringContainsString('use Symfony\Component\Uid\Ulid;', $resetPasswordRequestEntityContents);
                self::assertStringContainsString('[ORM\CustomIdGenerator(class: \'doctrine.ulid_generator\')]', $resetPasswordRequestEntityContents);

                $configFileContents = file_get_contents($runner->getPath('config/packages/reset_password.yaml'));

                // Flex recipe adds comments in reset_password.yaml, check file was replaced by maker
                self::assertStringNotContainsString('#', $configFileContents);

                $resetPasswordConfig = $runner->readYaml('config/packages/reset_password.yaml');

                self::assertSame('App\Repository\ResetPasswordRequestRepository', $resetPasswordConfig['symfonycasts_reset_password']['request_password_repository']);

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

        yield 'it_generates_with_translator_installed' => [self::buildMakerTest()
            // @legacy - drop skipped versions when PHP 8.1 is no longer supported.
            ->setSkippedPhpVersions(80100, 80109)
            ->addExtraDependencies('symfony/translation')
            ->run(static function (MakerTestRunner $runner) {
                self::makeUser($runner);

                $output = $runner->runMaker([
                    'app_home',
                    'victor@symfonycasts.com',
                    'SymfonyCasts',
                ]);

                self::assertStringContainsString('Success', $output);
            }),
        ];

        yield 'it_generates_with_custom_config' => [self::buildMakerTest()
            // @legacy - drop skipped versions when PHP 8.1 is no longer supported.
            ->setSkippedPhpVersions(80100, 80109)
            ->run(static function (MakerTestRunner $runner) {
                $runner->deleteFile('config/packages/reset_password.yaml');
                $runner->writeFile(
                    'config/packages/custom_reset_password.yaml',
                    Yaml::dump(['symfonycasts_reset_password' => [
                        'request_password_repository' => 'symfonycasts.reset_password.fake_request_repository',
                    ]])
                );

                self::makeUser($runner);

                $output = $runner->runMaker([
                    'app_home',
                    'jr@rushlow.dev',
                    'SymfonyCasts',
                ]);

                self::assertStringContainsString('Success', $output);

                self::assertFileDoesNotExist($runner->getPath('config/packages/reset_password.yaml'));
                self::assertStringContainsString(
                    'Just remember to set the request_password_repository in your configuration.',
                    $output
                );
            }),
        ];

        yield 'it_amends_configuration' => [self::buildMakerTest()
            // @legacy - drop skipped versions when PHP 8.1 is no longer supported.
            ->setSkippedPhpVersions(80100, 80109)
            ->run(static function (MakerTestRunner $runner) {
                $runner->modifyYamlFile('config/packages/reset_password.yaml', static function (array $config) {
                    $config['symfonycasts_reset_password']['lifetime'] = 9999;

                    return $config;
                });

                self::makeUser($runner);

                $output = $runner->runMaker([
                    'app_home',
                    'jr@rushlow.dev',
                    'SymfonyCasts',
                ]);

                self::assertStringContainsString('Success', $output);

                $resetPasswordConfig = $runner->readYaml('config/packages/reset_password.yaml');

                self::assertStringContainsString('9999', $resetPasswordConfig['symfonycasts_reset_password']['lifetime']);
                self::assertSame('App\Repository\ResetPasswordRequestRepository', $resetPasswordConfig['symfonycasts_reset_password']['request_password_repository']);
            }),
        ];

        yield 'it_generates_non_interactively' => [self::buildMakerTest()
            // @legacy - drop skipped versions when PHP 8.1 is no longer supported.
            ->setSkippedPhpVersions(80100, 80109)
            ->run(static function (MakerTestRunner $runner) {
                self::makeUser($runner);

                $output = $runner->runMaker(
                    [],
                    '--no-interaction --from-email-address=jr@rushlow.dev --from-email-name="Jesse Rushlow"'
                );

                self::assertStringContainsString('Success', $output);

                // the user class, the email field, the getter and the setter are all derived
                // from security.yaml and the class itself, exactly as the prompt derives them
                $controller = file_get_contents($runner->getPath('src/Controller/ResetPasswordController.php'));
                self::assertStringContainsString('getEmail()', $controller);
                self::assertStringContainsString('setPassword(', $controller);
                self::assertStringContainsString('app_home', $controller);
                self::assertStringContainsString('jr@rushlow.dev', $controller);
            }),
        ];

        yield 'it_rejects_missing_options_non_interactively' => [self::buildMakerTest()
            // @legacy - drop skipped versions when PHP 8.1 is no longer supported.
            ->setSkippedPhpVersions(80100, 80109)
            ->run(static function (MakerTestRunner $runner) {
                self::makeUser($runner);

                $invalid = [
                    '--no-interaction' => 'is not a valid email address',
                    '--no-interaction --from-email-address=jr@rushlow.dev' => 'This value cannot be blank',
                ];

                foreach ($invalid as $arguments => $expectedError) {
                    $output = $runner->runMaker([], $arguments, allowedToFail: true);

                    self::assertStringContainsString($expectedError, $output, \sprintf('"%s" was not rejected.', $arguments));
                    self::assertFileDoesNotExist($runner->getPath('src/Controller/ResetPasswordController.php'));
                }
            }),
        ];

        yield 'it_generates_with_custom_user' => [self::buildMakerTest()
            // @legacy - drop skipped versions when PHP 8.1 is no longer supported.
            ->setSkippedPhpVersions(80100, 80109)
            ->run(static function (MakerTestRunner $runner) {
                self::makeUser($runner, 'emailAddress', 'UserCustom', false);

                $runner->manipulateClass('src/Entity/UserCustom.php', static function (ClassSourceManipulator $manipulator) {
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

                self::assertStringContainsString('Success', $output);

                // check ResetPasswordController
                $contentResetPasswordController = file_get_contents($runner->getPath('src/Controller/ResetPasswordController.php'));
                self::assertStringContainsString('$form->get(\'emailAddress\')->getData()', $contentResetPasswordController);
                self::assertStringContainsString('\'emailAddress\' => $emailFormData,', $contentResetPasswordController);
                self::assertStringContainsString('$user->setMyPassword($passwordHasher->hashPassword($user, $plainPassword));', $contentResetPasswordController);
                self::assertStringContainsString('->to((string) $user->getEmailAddress())', $contentResetPasswordController);

                // check ResetPasswordRequest
                $contentResetPasswordRequest = file_get_contents($runner->getPath('src/Entity/ResetPasswordRequest.php'));

                self::assertStringContainsString('ORM\ManyToOne', $contentResetPasswordRequest);

                // check ResetPasswordRequestFormType
                $contentResetPasswordRequestFormType = file_get_contents($runner->getPath('/src/Form/ResetPasswordRequestFormType.php'));
                self::assertStringContainsString('->add(\'emailAddress\', EmailType::class, [', $contentResetPasswordRequestFormType);
                // check request.html.twig
                $contentRequestHtml = file_get_contents($runner->getPath('templates/reset_password/request.html.twig'));
                self::assertStringContainsString('{{ form_row(requestForm.emailAddress) }}', $contentRequestHtml);
            }),
        ];
    }

    private static function makeUser(MakerTestRunner $runner, string $identifier = 'email', string $userClass = 'User', bool $checkPassword = true): void
    {
        $runner->runConsole('make:user', [
            $userClass, // class name
            'y', // entity
            $identifier, // identifier
            $checkPassword ? 'y' : 'n', // password
        ]);
    }
}
