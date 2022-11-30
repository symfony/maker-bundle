<?php

/*
 * This file is part of the Symfony MakerBundle package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\MakerBundle\Security;

use Symfony\Bundle\MakerBundle\Util\YamlSourceManipulator;
use Symfony\Component\HttpKernel\Log\Logger;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

/**
 * @author Ryan Weaver   <ryan@symfonycasts.com>
 * @author Jesse Rushlow <jr@rushlow.dev>
 *
 * @internal
 */
final class SecurityConfigUpdater
{
    private ?YamlSourceManipulator $manipulator;

    public function __construct(
        private ?Logger $ysmLogger = null,
    ) {
    }

    public function updateForFormLogin(string $yamlSource, string $firewallToUpdate, string $loginPath, string $checkPath): string
    {
        $newData = $this->createYamlSourceManipulator($yamlSource);

        $newData['security']['firewalls'][$firewallToUpdate]['form_login']['login_path'] = $loginPath;
        $newData['security']['firewalls'][$firewallToUpdate]['form_login']['check_path'] = $checkPath;
        $newData['security']['firewalls'][$firewallToUpdate]['form_login']['enable_csrf'] = true;

        return $this->getYamlContentsFromData($newData);
    }

    public function updateForJsonLogin(string $yamlSource, string $firewallToUpdate, string $checkPath): string
    {
        $data = $this->createYamlSourceManipulator($yamlSource);

        $data['security']['firewalls'][$firewallToUpdate]['json_login']['check_path'] = $checkPath;

        return $this->getYamlContentsFromData($data);
    }

    /**
     * Updates security.yaml contents based on a new User class.
     */
    public function updateForUserClass(string $yamlSource, UserClassConfiguration $userConfig, string $userClass): string
    {
        $this->createYamlSourceManipulator($yamlSource);

        $this->updateProviders($userConfig, $userClass);

        if ($userConfig->hasPassword()) {
            $this->updatePasswordHashers($userConfig, $userClass);
        }

        $contents = $this->manipulator->getContents();
        $this->manipulator = null;

        return $contents;
    }

    public function updateForAuthenticator(string $yamlSource, string $firewallName, $chosenEntryPoint, string $authenticatorClass, bool $logoutSetup): string
    {
        $this->createYamlSourceManipulator($yamlSource);

        $newData = $this->manipulator->getData();

        if (!isset($newData['security']['firewalls'])) {
            if ($newData['security']) {
                $newData['security']['_firewalls'] = $this->manipulator->createEmptyLine();
            }

            $newData['security']['firewalls'] = [];
        }

        if (!isset($newData['security']['firewalls'][$firewallName])) {
            $newData['security']['firewalls'][$firewallName] = ['lazy' => true];
        }

        $firewall = $newData['security']['firewalls'][$firewallName];

        if (isset($firewall['custom_authenticator'])) {
            if (\is_array($firewall['custom_authenticator'])) {
                $firewall['custom_authenticator'][] = $authenticatorClass;
            } else {
                $stringValue = $firewall['custom_authenticator'];
                $firewall['custom_authenticator'] = [];
                $firewall['custom_authenticator'][] = $stringValue;
                $firewall['custom_authenticator'][] = $authenticatorClass;
            }
        } else {
            $firewall['custom_authenticator'] = $authenticatorClass;
        }

        if (!isset($firewall['entry_point']) && $chosenEntryPoint) {
            $firewall['entry_point_empty_line'] = $this->manipulator->createEmptyLine();
            $firewall['entry_point_comment'] = $this->manipulator->createCommentLine(
                ' the entry_point start() method determines what happens when an anonymous user accesses a protected page'
            );
            $firewall['entry_point'] = $authenticatorClass;
        }

        $newData['security']['firewalls'][$firewallName] = $firewall;

        if (!isset($firewall['logout']) && $logoutSetup) {
            $this->configureLogout($newData, $firewallName);

            return $this->manipulator->getContents();
        }

        $this->manipulator->setData($newData);

        return $this->manipulator->getContents();
    }

    public function updateForLogout(string $yamlSource, string $firewallName): string
    {
        $this->createYamlSourceManipulator($yamlSource);

        $this->configureLogout($this->manipulator->getData(), $firewallName);

        return $this->manipulator->getContents();
    }

    /**
     * @legacy This can be removed once we deprecate/remove `make:auth`
     */
    private function configureLogout(array $securityData, string $firewallName): void
    {
        $securityData['security']['firewalls'][$firewallName]['logout'] = ['path' => 'app_logout'];
        $securityData['security']['firewalls'][$firewallName]['logout'][] = $this->manipulator->createCommentLine(
            ' where to redirect after logout'
        );
        $securityData['security']['firewalls'][$firewallName]['logout'][] = $this->manipulator->createCommentLine(
            ' target: app_any_route'
        );

        $this->manipulator->setData($securityData);
    }

    private function createYamlSourceManipulator(string $yamlSource): array
    {
        $this->manipulator = new YamlSourceManipulator($yamlSource);

        if (null !== $this->ysmLogger) {
            $this->manipulator->setLogger($this->ysmLogger);
        }

        $this->normalizeSecurityYamlFile();

        return $this->manipulator->getData();
    }

    private function getYamlContentsFromData(array $yamlData): string
    {
        $this->manipulator->setData($yamlData);

        return $this->manipulator->getContents();
    }

    private function normalizeSecurityYamlFile(): void
    {
        if (!isset($this->manipulator->getData()['security'])) {
            $newData = $this->manipulator->getData();
            $newData['security'] = [];
            $this->manipulator->setData($newData);
        }
    }

    private function updateProviders(UserClassConfiguration $userConfig, string $userClass): void
    {
        $this->removeMemoryProviderIfIsSingleConfigured();

        $newData = $this->manipulator->getData();
        if ($newData['security'] && !\array_key_exists('providers', $newData['security'])) {
            $newData['security']['_providers'] = $this->manipulator->createEmptyLine();
        }

        $newData['security']['providers']['__'] = $this->manipulator->createCommentLine(
            ' used to reload user from session & other features (e.g. switch_user)'
        );
        if ($userConfig->isEntity()) {
            $newData['security']['providers']['app_user_provider'] = [
                'entity' => [
                    'class' => $userClass,
                    'property' => $userConfig->getIdentityPropertyName(),
                ],
            ];
        } else {
            if (!$userConfig->getUserProviderClass()) {
                throw new \LogicException('User provider class must be set for non-entity user.');
            }

            $newData['security']['providers']['app_user_provider'] = [
                'id' => $userConfig->getUserProviderClass(),
            ];
        }
        $this->manipulator->setData($newData);
    }

    private function updatePasswordHashers(UserClassConfiguration $userConfig, string $userClass): void
    {
        $newData = $this->manipulator->getData();

        if (isset($newData['security']['encoders'])) {
            throw new \RuntimeException('Password Encoders are no longer supported by MakerBundle. Please update your "config/packages/security.yaml" file to use Password Hashers instead.');
        }

        // The security-bundle recipe sets the password hasher via Flex. If it exists, move on...
        if (isset($newData['security']['password_hashers'][PasswordAuthenticatedUserInterface::class])) {
            return;
        }

        // by convention, password_hashers are put before the user provider option
        $providersIndex = array_search('providers', array_keys($newData['security']));

        if (false === $providersIndex) {
            $newData['security'] = ['password_hashers' => []] + $newData['security'];
        } else {
            $newData['security'] = array_merge(
                \array_slice($newData['security'], 0, $providersIndex),
                ['password_hashers' => []],
                \array_slice($newData['security'], $providersIndex)
            );
        }

        $newData['security']['password_hashers'][$userClass] = [
            'algorithm' => 'auto',
        ];

        $newData['security']['password_hashers']['_'] = $this->manipulator->createEmptyLine();

        $this->manipulator->setData($newData);
    }

    private function removeMemoryProviderIfIsSingleConfigured(): void
    {
        if (!$this->isSingleInMemoryProviderConfigured()) {
            return;
        }

        $newData = $this->manipulator->getData();

        $memoryProviderName = array_keys($newData['security']['providers'])[0];

        $newData['security']['providers'] = [];

        foreach ($newData['security']['firewalls'] as &$firewall) {
            if (($firewall['provider'] ?? null) === $memoryProviderName) {
                $firewall['provider'] = 'app_user_provider';
            }
        }

        $this->manipulator->setData($newData);
    }

    private function isSingleInMemoryProviderConfigured(): bool
    {
        if (!isset($this->manipulator->getData()['security']['providers'])) {
            return false;
        }

        $providersConfig = $this->manipulator->getData()['security']['providers'];

        if (1 !== \count($providersConfig)) {
            return false;
        }

        $firstProviderConfig = array_values($providersConfig)[0];

        return \array_key_exists('memory', $firstProviderConfig);
    }
}
