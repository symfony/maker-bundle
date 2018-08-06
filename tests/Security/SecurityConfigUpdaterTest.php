<?php

namespace Symfony\Bundle\MakerBundle\Tests\Security;

use PHPUnit\Framework\TestCase;
use Symfony\Bundle\MakerBundle\Security\SecurityConfigUpdater;
use Symfony\Bundle\MakerBundle\Security\UserClassConfiguration;

class SecurityConfigUpdaterTest extends TestCase
{
    /**
     * @dataProvider getUserClassTests
     */
    public function testUpdateForUserClass(UserClassConfiguration $userConfig, string $expectedSourceFilename, string $startingSourceFilename = 'simple_security.yaml')
    {
        $userClass = $userConfig->isEntity() ? 'App\\Entity\\User' : 'App\\Security\\User';
        if (!$userConfig->isEntity()) {
            $userConfig->setUserProviderClass('App\\Security\\UserProvider');
        }

        $updater = new SecurityConfigUpdater();
        $source = file_get_contents(__DIR__.'/yaml_fixtures/source/'.$startingSourceFilename);
        $actualSource = $updater->updateForUserClass($source, $userConfig, $userClass);
        $expectedSource = file_get_contents(__DIR__.'/yaml_fixtures/expected_user_class/'.$expectedSourceFilename);

        $this->assertSame($expectedSource, $actualSource);
    }

    public function getUserClassTests()
    {
        yield 'entity_email_password' => [
            new UserClassConfiguration(true, 'email', true),
            'entity_email_with_password.yaml',
        ];

        yield 'entity_username_no_password' => [
            new UserClassConfiguration(true, 'username', false),
            'entity_username_no_password.yaml',
        ];

        yield 'model_email_password' => [
            new UserClassConfiguration(false, 'email', true),
            'model_email_with_password.yaml',
        ];

        yield 'model_username_no_password' => [
            new UserClassConfiguration(false, 'username', false),
            'model_username_no_password.yaml',
        ];

        $config = new UserClassConfiguration(false, 'email', true);
        $config->useArgon2(false);
        yield 'model_email_password_bcrypt' => [
            $config,
            'model_email_password_bcrypt.yaml',
        ];

        yield 'model_email_password_existing_providers' => [
            new UserClassConfiguration(false, 'email', true),
            'model_email_password_existing_providers.yaml',
            'multiple_providers_security.yaml'
        ];

        yield 'empty_source_model_email_password' => [
            new UserClassConfiguration(false, 'email', true),
            'empty_source_model_email_with_password.yaml',
            'empty_security.yaml'
        ];
    }
}