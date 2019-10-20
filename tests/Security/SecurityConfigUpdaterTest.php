<?php

/*
 * This file is part of the Symfony MakerBundle package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\MakerBundle\Tests\Security;

use PHPUnit\Framework\TestCase;
use Symfony\Bundle\MakerBundle\Security\SecurityConfigUpdater;
use Symfony\Bundle\MakerBundle\Security\UserClassConfiguration;
use Symfony\Component\Security\Core\Encoder\NativePasswordEncoder;

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

        $bcryptOrAuto = class_exists(NativePasswordEncoder::class) ? 'auto' : 'bcrypt';
        $expectedSource = str_replace('{BCRYPT_OR_AUTO}', $bcryptOrAuto, $expectedSource);

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

        yield 'model_email_password_existing_providers' => [
            new UserClassConfiguration(false, 'email', true),
            'model_email_password_existing_providers.yaml',
            'multiple_providers_security.yaml',
        ];

        yield 'empty_source_model_email_password' => [
            new UserClassConfiguration(false, 'email', true),
            'empty_source_model_email_with_password.yaml',
            'empty_security.yaml',
        ];
    }

    /**
     * @dataProvider getAuthenticatorTests
     */
    public function testUpdateForAuthenticator(string $firewallName, $entryPoint, string $expectedSourceFilename, string $startingSourceFilename, bool $logoutSetup)
    {
        $updater = new SecurityConfigUpdater();
        $source = file_get_contents(__DIR__.'/yaml_fixtures/source/'.$startingSourceFilename);
        $actualSource = $updater->updateForAuthenticator($source, $firewallName, $entryPoint, 'App\\Security\\AppCustomAuthenticator', $logoutSetup);
        $expectedSource = file_get_contents(__DIR__.'/yaml_fixtures/expected_authenticator/'.$expectedSourceFilename);

        $this->assertSame($expectedSource, $actualSource);
    }

    public function getAuthenticatorTests()
    {
        yield 'empty_source' => [
            'main',
            null,
            'empty_source.yaml',
            'empty_security.yaml',
            false,
        ];

        yield 'simple_security' => [
            'main',
            null,
            'simple_security_source.yaml',
            'simple_security.yaml',
            false,
        ];

        yield 'simple_security_with_firewalls' => [
            'main',
            null,
            'simple_security_with_firewalls.yaml',
            'simple_security_with_firewalls.yaml',
            false,
        ];

        yield 'simple_security_with_firewalls_and_authenticator' => [
            'main',
            'App\\Security\\AppCustomAuthenticator',
            'simple_security_with_firewalls_and_authenticator.yaml',
            'simple_security_with_firewalls_and_authenticator.yaml',
            false,
        ];

        yield 'simple_security_with_firewalls_and_logout' => [
            'main',
            'App\\Security\\AppCustomAuthenticator',
            'simple_security_with_firewalls_and_logout.yaml',
            'simple_security_with_firewalls_and_logout.yaml',
            true,
        ];
    }
}
