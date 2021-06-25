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
use Symfony\Component\PasswordHasher\Hasher\NativePasswordHasher;
use Symfony\Component\Security\Core\Encoder\NativePasswordEncoder;

/**
 * @author Ryan Weaver   <ryan@symfonycasts.com>
 * @author Jesse Rushlow <jr@rushlow.dev>
 *
 * @internal
 */
final class SecurityConfigUpdater
{
    /** @var YamlSourceManipulator */
    private $manipulator;

    /** @var Logger|null */
    private $ysmLogger;

    public function __construct(Logger $ysmLogger = null)
    {
        $this->ysmLogger = $ysmLogger;
    }

    /**
     * Updates security.yaml contents based on a new User class.
     */
    public function updateForUserClass(string $yamlSource, UserClassConfiguration $userConfig, string $userClass): string
    {
        $this->manipulator = new YamlSourceManipulator($yamlSource);

        if (null !== $this->ysmLogger) {
            $this->manipulator->setLogger($this->ysmLogger);
        }

        $this->normalizeSecurityYamlFile();

        $this->updateProviders($userConfig, $userClass);

        if ($userConfig->hasPassword()) {
            $symfonyGte53 = class_exists(NativePasswordHasher::class);
            $this->updatePasswordHashers($userConfig, $userClass, $symfonyGte53 ? 'password_hashers' : 'encoders');
        }

        $contents = $this->manipulator->getContents();
        $this->manipulator = null;

        return $contents;
    }

    public function updateForAuthenticator(string $yamlSource, string $firewallName, $chosenEntryPoint, string $authenticatorClass, bool $logoutSetup, bool $useSecurity52): string
    {
        $this->manipulator = new YamlSourceManipulator($yamlSource);

        if (null !== $this->ysmLogger) {
            $this->manipulator->setLogger($this->ysmLogger);
        }

        $this->normalizeSecurityYamlFile();

        $newData = $this->manipulator->getData();

        if (!isset($newData['security']['firewalls'])) {
            if ($newData['security']) {
                $newData['security']['_firewalls'] = $this->manipulator->createEmptyLine();
            }

            $newData['security']['firewalls'] = [];
        }

        if (!isset($newData['security']['firewalls'][$firewallName])) {
            if ($useSecurity52) {
                $newData['security']['firewalls'][$firewallName] = ['lazy' => true];
            } else {
                $newData['security']['firewalls'][$firewallName] = ['anonymous' => 'lazy'];
            }
        }

        $firewall = $newData['security']['firewalls'][$firewallName];

        if ($useSecurity52) {
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
        } else {
            if (!isset($firewall['guard'])) {
                $firewall['guard'] = [];
            }

            if (!isset($firewall['guard']['authenticators'])) {
                $firewall['guard']['authenticators'] = [];
            }

            $firewall['guard']['authenticators'][] = $authenticatorClass;

            if (\count($firewall['guard']['authenticators']) > 1) {
                $firewall['guard']['entry_point'] = $chosenEntryPoint ?? current($firewall['guard']['authenticators']);
            }
        }

        if (!isset($firewall['logout']) && $logoutSetup) {
            $firewall['logout'] = ['path' => 'app_logout'];
            $firewall['logout'][] = $this->manipulator->createCommentLine(
                ' where to redirect after logout'
            );
            $firewall['logout'][] = $this->manipulator->createCommentLine(
                ' target: app_any_route'
            );
        }

        $newData['security']['firewalls'][$firewallName] = $firewall;

        $this->manipulator->setData($newData);

        return $this->manipulator->getContents();
    }

    private function normalizeSecurityYamlFile()
    {
        if (!isset($this->manipulator->getData()['security'])) {
            $newData = $this->manipulator->getData();
            $newData['security'] = [];
            $this->manipulator->setData($newData);
        }
    }

    private function updateProviders(UserClassConfiguration $userConfig, string $userClass)
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

    private function updatePasswordHashers(UserClassConfiguration $userConfig, string $userClass, string $keyName = 'password_hashers')
    {
        $newData = $this->manipulator->getData();
        if ('password_hashers' === $keyName && isset($newData['security']['encoders'])) {
            // fallback to "encoders" if the user already defined encoder config
            $this->updatePasswordHashers($userClass, $userClass, 'encoders');

            return;
        }

        if (!isset($newData['security'][$keyName])) {
            // by convention, password_hashers are put before the user provider option
            $providersIndex = array_search('providers', array_keys($newData['security']));
            if (false === $providersIndex) {
                $newData['security'] = [$keyName => []] + $newData['security'];
            } else {
                $newData['security'] = array_merge(
                    \array_slice($newData['security'], 0, $providersIndex),
                    [$keyName => []],
                    \array_slice($newData['security'], $providersIndex)
                );
            }
        }

        $newData['security'][$keyName][$userClass] = [
            'algorithm' => $userConfig->shouldUseArgon2() ? 'argon2i' : ((class_exists(NativePasswordHasher::class) || class_exists(NativePasswordEncoder::class)) ? 'auto' : 'bcrypt'),
        ];
        $newData['security'][$keyName]['_'] = $this->manipulator->createEmptyLine();

        $this->manipulator->setData($newData);
    }

    private function removeMemoryProviderIfIsSingleConfigured()
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
