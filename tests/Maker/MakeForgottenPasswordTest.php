<?php

namespace Symfony\Bundle\MakerBundle\Tests\Maker;

use Symfony\Bundle\MakerBundle\Maker\MakeForgottenPassword;
use Symfony\Bundle\MakerBundle\Test\MakerTestCase;
use Symfony\Bundle\MakerBundle\Test\MakerTestDetails;

class MakeForgottenPasswordTest extends MakerTestCase
{
    /**
     * Test skipped until make forgotten password is enabled again.
     * @see https://github.com/symfony/maker-bundle/issues/537
     */
    public function getTestDetails()
    {
        $this->markTestSkipped('Temp. Disabled make forgotten password. See ');

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
            ->addExtraDependencies('symfony/swiftmailer-bundle')
        ];
    }
}
