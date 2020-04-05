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
use Symfony\Bundle\MakerBundle\Test\MakerTestDetails;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Yaml;

class MakeResetPasswordTest extends MakerTestCase
{
    public function getTestDetails()
    {
        yield 'reset_password_replaces_flex_config' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeResetPassword::class),
            [
                'App\Entity\User',
                'app_home',
                'jr@rushlow.dev',
                'SymfonyCasts',
            ])
            ->setRequiredPhpVersion(70200)
            ->addExtraDependencies('security-bundle')
            ->addExtraDependencies('twig')
            ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeResetPassword')
            ->assert(
                function (string $output, string $directory) {
                    $this->assertStringContainsString('Success', $output);

                    $fs = new Filesystem();

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
                        $this->assertTrue($fs->exists(sprintf('%s/%s', $directory, $file)));
                    }

                    $configFileContents = file_get_contents(sprintf('%s/config/packages/reset_password.yaml', $directory));

                    // Flex recipe adds comments in reset_password.yaml, check file was replaced by maker
                    $this->assertStringNotContainsString('#', $configFileContents);

                    $resetPasswordConfig = Yaml::parse($configFileContents);

                    $this->assertSame('App\Repository\ResetPasswordRequestRepository', $resetPasswordConfig['symfonycasts_reset_password']['request_password_repository']);
                }
            ),
        ];

        yield 'reset_password_custom_config' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeResetPassword::class),
            [
                'App\Entity\User',
                'app_home',
                'jr@rushlow.dev',
                'SymfonyCasts',
            ])
            ->setRequiredPhpVersion(70200)
            ->addExtraDependencies('security-bundle')
            ->addExtraDependencies('twig')
            ->deleteFile('config/packages/reset_password.yaml')
            ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeResetPasswordCustomConfig')
            ->assert(
                function (string $output, string $directory) {
                    $this->assertStringContainsString('Success', $output);

                    $fs = new Filesystem();
                    $this->assertFalse($fs->exists(sprintf('%s/config/packages/reset_password.yaml', $directory)));
                    $this->assertStringContainsString(
                        'Just remember to set the request_password_repository in your configuration.',
                        $output
                    );
                }
            ),
        ];

        yield 'reset_password_amends_config' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeResetPassword::class),
            [
                'App\Entity\User',
                'app_home',
                'jr@rushlow.dev',
                'SymfonyCasts',
            ])
            ->setRequiredPhpVersion(70200)
            ->addExtraDependencies('security-bundle')
            ->addExtraDependencies('twig')
            ->addReplacement(
                'config/packages/reset_password.yaml',
                'symfonycasts_reset_password:',
                Yaml::dump(['symfonycasts_reset_password' => ['lifetime' => 9999]])
            )
            ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeResetPasswordModifiedConfig')
            ->assert(
                function (string $output, string $directory) {
                    $this->assertStringContainsString('Success', $output);

                    $configFileContents = file_get_contents(sprintf('%s/config/packages/reset_password.yaml', $directory));

                    $resetPasswordConfig = Yaml::parse($configFileContents);

                    $this->assertStringContainsString('9999', $resetPasswordConfig['symfonycasts_reset_password']['lifetime']);
                    $this->assertSame('App\Repository\ResetPasswordRequestRepository', $resetPasswordConfig['symfonycasts_reset_password']['request_password_repository']);
                }
            ),
        ];

        yield 'reset_password_functional_test' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeResetPassword::class),
            [
                'App\Entity\User',
                'app_home',
                'jr@rushlow.dev',
                'SymfonyCasts',
            ])
            ->setRequiredPhpVersion(70200)
            ->addExtraDependencies('doctrine')
            ->addExtraDependencies('doctrine/annotations')
            ->addExtraDependencies('mailer')
            ->addExtraDependencies('security-bundle')
            ->addExtraDependencies('symfony/form')
            ->addExtraDependencies('symfony/validator')
            ->addExtraDependencies('twig')
            ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeResetPasswordFunctionalTest'),
        ];
    }
}
