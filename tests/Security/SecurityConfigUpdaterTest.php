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
use Psr\Log\LogLevel;
use Symfony\Bundle\MakerBundle\Security\SecurityConfigUpdater;
use Symfony\Bundle\MakerBundle\Security\UserClassConfiguration;
use Symfony\Component\HttpKernel\Log\Logger;

class SecurityConfigUpdaterTest extends TestCase
{
    /**
     * Set to true to enable low level debug logging during tests for
     * the YamlSourceManipulator.
     */
    private bool $enableYsmLogging = false;
    private ?Logger $ysmLogger = null;

    /**
     * @dataProvider getUserClassTests
     */
    public function testUpdateForUserClass(UserClassConfiguration $userConfig, string $expectedSourceFilename, string $startingSourceFilename = 'simple_security.yaml'): void
    {
        $this->createLogger();

        $userClass = $userConfig->isEntity() ? 'App\\Entity\\User' : 'App\\Security\\User';
        if (!$userConfig->isEntity()) {
            $userConfig->setUserProviderClass('App\\Security\\UserProvider');
        }

        $updater = new SecurityConfigUpdater($this->ysmLogger);
        $source = $this->getYamlSource($startingSourceFilename);
        $actualSource = $updater->updateForUserClass($source, $userConfig, $userClass);
        $expectedSource = $this->getExpectedYaml('expected_user_class/5.3', $expectedSourceFilename);

        $expectedSource = str_replace('{BCRYPT_OR_AUTO}', 'auto', $expectedSource);

        $this->assertSame($expectedSource, $actualSource);
    }

    public function getUserClassTests(): \Generator
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

        yield 'simple_security_with_single_memory_provider_configured' => [
            new UserClassConfiguration(true, 'email', true),
            'simple_security_with_single_memory_provider_configured.yaml',
            'simple_security_with_single_memory_provider_configured.yaml',
        ];

        yield 'security_52_empty_security' => [
            new UserClassConfiguration(true, 'email', true),
            'security_52_entity_email_with_password.yaml',
            'empty_security.yaml',
        ];

        yield 'security_52_with_firewalls_and_logout' => [
            new UserClassConfiguration(true, 'email', true),
            'security_52_complex_entity_email_with_password.yaml',
            'simple_security_with_firewalls_and_logout.yaml',
        ];
    }

    /**
     * @dataProvider getAuthenticatorTests
     */
    public function testUpdateForAuthenticator(string $firewallName, $entryPoint, string $expectedSourceFilename, string $startingSourceFilename, bool $logoutSetup, bool $supportRememberMe, bool $alwaysRememberMe): void
    {
        $this->createLogger();

        $updater = new SecurityConfigUpdater($this->ysmLogger);
        $source = file_get_contents(__DIR__.'/yaml_fixtures/source/'.$startingSourceFilename);
        $actualSource = $updater->updateForAuthenticator($source, $firewallName, $entryPoint, 'App\\Security\\AppCustomAuthenticator', $logoutSetup, $supportRememberMe, $alwaysRememberMe);
        $expectedSource = file_get_contents(__DIR__.'/yaml_fixtures/expected_authenticator/'.$expectedSourceFilename);

        $this->assertSame($expectedSource, $actualSource);
    }

    public function getAuthenticatorTests(): \Generator
    {
        yield 'empty_source' => [
            'main',
            null,
            'empty_source.yaml',
            'empty_security.yaml',
            false,
            false,
            false,
        ];

        yield 'simple_security' => [
            'main',
            null,
            'simple_security_source.yaml',
            'simple_security.yaml',
            false,
            false,
            false,
        ];

        yield 'simple_security_with_firewalls' => [
            'main',
            null,
            'simple_security_with_firewalls.yaml',
            'simple_security_with_firewalls.yaml',
            false,
            false,
            false,
        ];

        yield 'simple_security_with_firewalls_and_authenticator' => [
            'main',
            'App\\Security\\AppCustomAuthenticator',
            'simple_security_with_firewalls_and_authenticator.yaml',
            'simple_security_with_firewalls_and_authenticator.yaml',
            false,
            false,
            false,
        ];

        yield 'simple_security_with_firewalls_and_logout' => [
            'main',
            'App\\Security\\AppCustomAuthenticator',
            'simple_security_with_firewalls_and_logout.yaml',
            'simple_security_with_firewalls_and_logout.yaml',
            true,
            false,
            false,
        ];

        yield 'security_52_with_multiple_authenticators' => [
            'main',
            'App\\Security\\AppCustomAuthenticator',
            'multiple_authenticators.yaml',
            'multiple_authenticators.yaml',
            false,
            false,
            false,
        ];

        yield 'simple_security_with_firewalls_and_remember_me_checkbox' => [
            'main',
            null,
            'simple_security_with_firewalls_and_remember_me_checkbox.yaml',
            'simple_security.yaml',
            false,
            true,
            false,
        ];

        yield 'simple_security_with_firewalls_and_always_remember_me' => [
            'main',
            null,
            'simple_security_with_firewalls_and_always_remember_me.yaml',
            'simple_security.yaml',
            false,
            true,
            true,
        ];
    }

    public function testUpdateForFormLogin(): void
    {
        $this->createLogger();

        $updater = new SecurityConfigUpdater($this->ysmLogger);
        $source = $this->getYamlSource('empty_security.yaml');

        $actualSource = $updater->updateForFormLogin($source, 'main', 'a_login_path', 'a_check_path');

        $this->assertSame(
            $this->getExpectedYaml('expected_form_login', 'form_login.yaml'),
            $actualSource
        );
    }

    public function testUpdateForJsonLogin(): void
    {
        $this->createLogger();

        $updater = new SecurityConfigUpdater($this->ysmLogger);
        $source = $this->getYamlSource('empty_security.yaml');

        $actualSource = $updater->updateForJsonLogin($source, 'main', 'a_check_path');

        $this->assertSame(
            $this->getExpectedYaml('expected_json_login', 'json_login.yaml'),
            $actualSource
        );
    }

    public function testUpdateForLogout(): void
    {
        $this->createLogger();

        $updater = new SecurityConfigUpdater($this->ysmLogger);
        $source = $this->getYamlSource('simple_security_with_firewalls.yaml');

        $actualSource = $updater->updateForLogout($source, 'main');

        $this->assertSame(
            $this->getExpectedYaml('expected_logout', 'logout.yaml'),
            $actualSource
        );
    }

    private function createLogger(): void
    {
        if (!$this->enableYsmLogging) {
            return;
        }

        $this->ysmLogger = new Logger(LogLevel::DEBUG, 'php://stdout', function (string $level, string $message, array $context) {
            $maxLen = max(array_map('strlen', array_keys($context)));

            foreach ($context as $key => $val) {
                $message .= \sprintf(
                    "\n    %s%s: %s",
                    str_repeat(' ', $maxLen - \strlen($key)),
                    $key,
                    $val
                );
            }

            return $message."\n\n";
        });
    }

    private function getYamlSource(string $yamlFileName): string
    {
        return file_get_contents(\sprintf('%s/yaml_fixtures/source/%s', __DIR__, $yamlFileName));
    }

    private function getExpectedYaml(string $subDirectory, string $yamlFileName): string
    {
        return file_get_contents(\sprintf('%s/yaml_fixtures/%s/%s', __DIR__, $subDirectory, $yamlFileName));
    }
}
