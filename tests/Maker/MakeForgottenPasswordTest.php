<?php

namespace Symfony\Bundle\MakerBundle\Tests\Maker;

use Symfony\Bundle\MakerBundle\Maker\MakeForgottenPassword;
use Symfony\Bundle\MakerBundle\Test\MakerTestCase;
use Symfony\Bundle\MakerBundle\Test\MakerTestDetails;

class MakeForgottenPasswordTest extends MakerTestCase
{
    public function getTestDetails()
    {
        yield 'forgotten_password' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeForgottenPassword::class),
            [
                'App\\Entity\\User',
                // email field guessed
                // email getter guessed
                // password setter guessed
            ])
            ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeForgottenPassword')
            ->configureDatabase()
            ->updateSchemaAfterCommand()
        ];
    }
}
