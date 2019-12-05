<?php

namespace Symfony\Bundle\MakerBundle\Tests\Maker;

use Symfony\Bundle\MakerBundle\Maker\MakeRegistrationForm;
use Symfony\Bundle\MakerBundle\Test\MakerTestCase;
use Symfony\Bundle\MakerBundle\Test\MakerTestDetails;

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
                // firewall name guessed
                '', // yes to add UniqueEntity
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
            ->addPostMakeCommand('php bin/console cache:clear --env=test')
        ];

        // sanity check on all the interactive questions
        yield 'registration_form_no_guessing' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeRegistrationForm::class),
            [
                'App\\Entity\\User',
                'emailAlt', // username field
                'passwordAlt', // password field
                'n', // no UniqueEntity
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
                'n', // no authenticate after
                'app_anonymous', // route name to redirect to
            ])
            ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeRegistrationFormEntity')
            ->configureDatabase()
            ->updateSchemaAfterCommand()
            // workaround for strange failure - see test case
            // registration_form_entity_guard_authenticate for details
            ->addPostMakeCommand('php bin/console cache:clear --env=test')
        ];
    }
}
