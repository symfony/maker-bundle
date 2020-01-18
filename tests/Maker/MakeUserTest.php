<?php

namespace Symfony\Bundle\MakerBundle\Tests\Maker;

use Symfony\Bundle\MakerBundle\Maker\MakeUser;
use Symfony\Bundle\MakerBundle\Test\MakerTestCase;
use Symfony\Bundle\MakerBundle\Test\MakerTestDetails;

class MakeUserTest extends MakerTestCase
{
    public function getTestDetails()
    {
        yield 'user_security_entity_with_password' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeUser::class),
            [
                // user class name
                'User',
                'y', // entity
                'email', // identity property
                'y', // with password
                'y', // argon
            ])
            ->addExtraDependencies('doctrine')
            ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeUserEntityPassword')
            ->configureDatabase()
            ->addExtraDependencies('doctrine')
            ->setGuardAuthenticator('main', 'App\\Security\\AutomaticAuthenticator')
            ->setRequiredPhpVersion(70100)
            ->updateSchemaAfterCommand(),
        ];

        yield 'user_security_model_no_password' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeUser::class),
            [
                // user class name (with non-traditional name)
                'FunUser',
                'n', // entity
                'username', // identity property
                'n', // login with password?
            ])
            ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeUserModelNoPassword')
            ->setGuardAuthenticator('main', 'App\\Security\\AutomaticAuthenticator')
            ->setRequiredPhpVersion(70100)
            ->addPostMakeReplacement(
                'src/Security/UserProvider.php',
                'throw new \Exception(\'TODO: fill in refreshUser() inside \'.__FILE__);',
                'return $user;'
            ),
        ];
    }
}
