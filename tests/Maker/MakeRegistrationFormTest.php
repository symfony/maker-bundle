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

use Symfony\Bundle\MakerBundle\Maker\MakeRegistrationForm;
use Symfony\Bundle\MakerBundle\Test\MakerTestCase;
use Symfony\Bundle\MakerBundle\Test\MakerTestDetails;
use Symfony\Component\Filesystem\Filesystem;

class MakeRegistrationFormTest extends MakerTestCase
{
    public function getTestDetails()
    {
        yield 'registration_form_entity_guard_authenticate' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeRegistrationForm::class),
            [
                // user class guessed,
                // username field guessed
                // password guessed
                '', // yes to add UniqueEntity
                'n', // verify user
                // firewall name guessed
                '', // yes authenticate after
                // 1 authenticator will be guessed
            ])
            ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeRegistrationFormEntity')
            ->configureDatabase()
            ->updateSchemaAfterCommand()
            // workaround for a strange behavior where, every other
            // test run, the UniqueEntity would not be seen, because
            // the the validation cache was out of date. The cause
            // is currently unknown, so this workaround was added
            ->addPostMakeCommand('php bin/console cache:clear --env=test'),
        ];

        // sanity check on all the interactive questions
        yield 'registration_form_no_guessing' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeRegistrationForm::class),
            [
                'App\\Entity\\User',
                'emailAlt', // username field
                'passwordAlt', // password field
                'n', // no UniqueEntity
                'n', // no verify user
                '', // yes authenticate after
                'main', // firewall
                '1', // authenticator
            ])
            ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeRegistrationFormNoGuessing'),
        ];

        yield 'registration_form_entity_no_authenticate' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeRegistrationForm::class),
            [
                // all basic data guessed
                'y', // add UniqueEntity
                'n', // no verify user
                'n', // no authenticate after
                'app_anonymous', // route name to redirect to
            ])
            ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeRegistrationFormEntity')
            ->configureDatabase()
            ->updateSchemaAfterCommand()
            // workaround for strange failure - see test case
            // registration_form_entity_guard_authenticate for details
            ->addPostMakeCommand('php bin/console cache:clear --env=test'),
        ];

        yield 'registration_form_with_email_verification' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeRegistrationForm::class),
            [
                'n', // add UniqueEntity
                'y', // no verify user
                'jr@rushlow.dev', // from email address
                'SymfonyCasts', // From Name
                'n', // no authenticate after
                0, // route number to redirect to
            ])
            ->setRequiredPhpVersion(70200)
            ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeRegistrationFormVerifyEmail')
            ->addExtraDependencies('symfonycasts/verify-email-bundle')
            ->assert(
                function (string $output, string $directory) {
                    $this->assertStringContainsString('Success', $output);

                    $fs = new Filesystem();

                    $generatedFiles = [
                        'src/Security/EmailVerifier.php',
                        'templates/registration/confirmation_email.html.twig',
                    ];

                    foreach ($generatedFiles as $file) {
                        $this->assertTrue($fs->exists(sprintf('%s/%s', $directory, $file)));
                    }
                }
            ),
        ];

        yield 'verify_email_functional_test' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeRegistrationForm::class),
            [
                'n', // add UniqueEntity
                'y', // no verify user
                'jr@rushlow.dev', // from email address
                'SymfonyCasts', // From Name
                '', // yes authenticate after
                'app_register',
            ])
            ->setRequiredPhpVersion(70200)
            ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeRegistrationFormVerifyEmailFunctionalTest')
            ->addExtraDependencies('symfonycasts/verify-email-bundle')
            ->configureDatabase()
            ->updateSchemaAfterCommand()
            // needed for internal functional test
            ->addExtraDependencies('symfony/web-profiler-bundle')
            ->addExtraDependencies('mailer'),
        ];
    }
}
