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

use Symfony\Bundle\MakerBundle\Security\Model\Authenticator;
use Symfony\Bundle\MakerBundle\Security\Model\AuthenticatorType;
use Symfony\Bundle\MakerBundle\Str;
use Symfony\Bundle\MakerBundle\Validator;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @internal
 */
final class InteractiveSecurityHelper
{
    public function guessFirewallName(SymfonyStyle $io, array $securityData, ?string $questionText = null): string
    {
        if (null !== $firewallName = $this->findFirewallName($securityData)) {
            return $firewallName;
        }

        $realFirewalls = $this->realFirewalls($securityData);

        return $io->choice(
            $questionText ?? 'Which firewall do you want to update?',
            array_keys($realFirewalls),
            key($realFirewalls)
        );
    }

    /**
     * The firewall when there is only one sensible answer, null when a person has to pick.
     */
    public function findFirewallName(array $securityData): ?string
    {
        $realFirewalls = $this->realFirewalls($securityData);

        if (!$realFirewalls) {
            return 'main';
        }

        if (1 === \count($realFirewalls)) {
            return key($realFirewalls);
        }

        return null;
    }

    private function realFirewalls(array $securityData): array
    {
        return array_filter(
            $securityData['security']['firewalls'] ?? [],
            static fn ($item) => !isset($item['security']) || true === $item['security']
        );
    }

    public function guessUserClass(SymfonyStyle $io, array $providers, ?string $questionText = null): string
    {
        if (null !== $userClass = $this->findUserClass($providers)) {
            return $userClass;
        }

        return $io->ask(
            $questionText ?? 'Enter the User class that you want to authenticate (e.g. <fg=yellow>App\\Entity\\User</>)',
            $this->guessUserClassDefault(),
            Validator::classIsUserInterface(...)
        );
    }

    /**
     * The user class when a single entity provider names one, null when a person has to answer.
     */
    public function findUserClass(array $providers): ?string
    {
        if (1 === \count($providers) && isset(current($providers)['entity']['class'])) {
            return current($providers)['entity']['class'];
        }

        return null;
    }

    private function guessUserClassDefault(): string
    {
        if (class_exists('App\\Entity\\User') && isset(class_implements('App\\Entity\\User')[UserInterface::class])) {
            return 'App\\Entity\\User';
        }

        if (class_exists('App\\Security\\User') && isset(class_implements('App\\Security\\User')[UserInterface::class])) {
            return 'App\\Security\\User';
        }

        return '';
    }

    public function guessUserNameField(SymfonyStyle $io, string $userClass, array $providers): string
    {
        if (null !== $userNameField = $this->findUserNameField($userClass, $providers)) {
            return $userNameField;
        }

        $classProperties = [];
        $reflectionClass = new \ReflectionClass($userClass);
        foreach ($reflectionClass->getProperties() as $property) {
            $classProperties[] = $property->name;
        }

        if (empty($classProperties)) {
            throw new \LogicException(\sprintf('No properties were found in "%s" entity', $userClass));
        }

        return $io->choice(
            \sprintf('Which field on your <fg=yellow>%s</> class will people enter when logging in?', $userClass),
            $classProperties,
            property_exists($userClass, 'username') ? 'username' : (property_exists($userClass, 'email') ? 'email' : null)
        );
    }

    /**
     * The login field when the provider or the class names one, null when a person has to pick.
     */
    public function findUserNameField(string $userClass, array $providers): ?string
    {
        if (1 === \count($providers) && isset(current($providers)['entity']['property'])) {
            return current($providers)['entity']['property'];
        }

        if (property_exists($userClass, 'email') && !property_exists($userClass, 'username')) {
            return 'email';
        }

        if (!property_exists($userClass, 'email') && property_exists($userClass, 'username')) {
            return 'username';
        }

        return null;
    }

    public function guessEmailField(SymfonyStyle $io, string $userClass): string
    {
        if (null !== $emailField = $this->findEmailField($userClass)) {
            return $emailField;
        }

        $classProperties = [];
        $reflectionClass = new \ReflectionClass($userClass);
        foreach ($reflectionClass->getProperties() as $property) {
            $classProperties[] = $property->name;
        }

        return $io->choice(
            \sprintf('Which field on your <fg=yellow>%s</> class holds the email address?', $userClass),
            $classProperties
        );
    }

    /**
     * The email property when the class has the obvious one, null when a person has to pick.
     */
    public function findEmailField(string $userClass): ?string
    {
        return property_exists($userClass, 'email') ? 'email' : null;
    }

    public function guessPasswordField(SymfonyStyle $io, string $userClass): string
    {
        if (null !== $passwordField = $this->findPasswordField($userClass)) {
            return $passwordField;
        }

        $classProperties = [];
        $reflectionClass = new \ReflectionClass($userClass);
        foreach ($reflectionClass->getProperties() as $property) {
            $classProperties[] = $property->name;
        }

        return $io->choice(
            \sprintf('Which field on your <fg=yellow>%s</> class holds the encoded password?', $userClass),
            $classProperties
        );
    }

    /**
     * The password property when the class has the obvious one, null when a person has to pick.
     */
    public function findPasswordField(string $userClass): ?string
    {
        return property_exists($userClass, 'password') ? 'password' : null;
    }

    public function guessPasswordSetter(SymfonyStyle $io, string $userClass): string
    {
        if (null !== $passwordSetter = $this->findPasswordSetter($userClass)) {
            return $passwordSetter;
        }

        $methodChoices = $this->methodNameGuesser($userClass, 'setPassword');

        return $io->choice(
            \sprintf('Which method on your <fg=yellow>%s</> class can be used to set the encoded password (e.g. setPassword())?', $userClass),
            $methodChoices
        );
    }

    /**
     * The password setter when the class has the obvious one, null when a person has to pick.
     */
    public function findPasswordSetter(string $userClass): ?string
    {
        return null === $this->methodNameGuesser($userClass, 'setPassword') ? 'setPassword' : null;
    }

    public function guessEmailGetter(SymfonyStyle $io, string $userClass, string $emailPropertyName): string
    {
        if (null !== $emailGetter = $this->findEmailGetter($userClass, $emailPropertyName)) {
            return $emailGetter;
        }

        $methodChoices = $this->methodNameGuesser($userClass, \sprintf('get%s', Str::asCamelCase($emailPropertyName)));

        return $io->choice(
            \sprintf('Which method on your <fg=yellow>%s</> class can be used to get the email address (e.g. getEmail())?', $userClass),
            $methodChoices
        );
    }

    /**
     * The email getter when the class has the obvious one, null when a person has to pick.
     */
    public function findEmailGetter(string $userClass, string $emailPropertyName): ?string
    {
        $supposedEmailMethodName = \sprintf('get%s', Str::asCamelCase($emailPropertyName));

        return null === $this->methodNameGuesser($userClass, $supposedEmailMethodName) ? $supposedEmailMethodName : null;
    }

    public function guessIdGetter(SymfonyStyle $io, string $userClass): string
    {
        if (null !== $idGetter = $this->findIdGetter($userClass)) {
            return $idGetter;
        }

        $methodChoices = $this->methodNameGuesser($userClass, 'getId');

        return $io->choice(
            \sprintf('Which method on your <fg=yellow>%s</> class can be used to get the unique user identifier (e.g. getId())?', $userClass),
            $methodChoices
        );
    }

    /**
     * @param array<string, array<string, mixed>> $firewalls Config data from security.firewalls
     *
     * @return Authenticator[]
     */
    public function getAuthenticatorsFromConfig(array $firewalls): array
    {
        $authenticators = [];

        /* Iterate over each firewall that exists e.g. security.firewalls.main
         * $firewallName could be "main" or "dev", etc...
         * $firewallConfig should be an array of the firewalls params
         */
        foreach ($firewalls as $firewallName => $firewallConfig) {
            if (!\is_array($firewallConfig)) {
                continue;
            }

            $authenticators = [
                ...$authenticators,
                ...$this->getAuthenticatorsFromConfigData($firewallConfig, $firewallName),
            ];
        }

        return $authenticators;
    }

    /**
     * Pass in a firewalls config e.g. security.firewalls.main like:
     *      pattern: ^/path
     *      form_login:
     *          login_path: app_login
     *      custom_authenticator:
     *          - App\Security\MyAuthenticator
     *
     * @param array<string, mixed> $firewallConfig
     *
     * @return Authenticator[]
     */
    private function getAuthenticatorsFromConfigData(array $firewallConfig, string $firewallName): array
    {
        $authenticators = [];

        foreach ($firewallConfig as $potentialAuthenticator => $configData) {
            // Check if $potentialAuthenticator is a supported authenticator or if its some other key.
            if (null === ($authenticator = AuthenticatorType::tryFrom($potentialAuthenticator))) {
                // $potentialAuthenticator is probably something like "pattern" or "lazy", not an authenticator
                continue;
            }

            // $potentialAuthenticator is a supported authenticator. Check if it's a custom_authenticator.
            if (AuthenticatorType::CUSTOM !== $authenticator) {
                // We found a "built in" authenticator - "form_login", "json_login", etc...
                $authenticators[] = new Authenticator($authenticator, $firewallName);

                continue;
            }

            /*
             * $potentialAuthenticator = custom_authenticator.
             * $configData is either [App\MyAuthenticator] or (string) App\MyAuthenticator
             */
            $customAuthenticators = $this->getCustomAuthenticators($configData, $firewallName);

            $authenticators = [...$authenticators, ...$customAuthenticators];
        }

        return $authenticators;
    }

    /**
     * @param string|array<string> $customAuthenticators A single entry from custom_authenticators or an array of authenticators
     *
     * @return Authenticator[]
     */
    private function getCustomAuthenticators(string|array $customAuthenticators, string $firewallName): array
    {
        if (\is_string($customAuthenticators)) {
            $customAuthenticators = [$customAuthenticators];
        }

        $authenticators = [];

        foreach ($customAuthenticators as $customAuthenticatorClass) {
            $authenticators[] = new Authenticator(AuthenticatorType::CUSTOM, $firewallName, $customAuthenticatorClass);
        }

        return $authenticators;
    }

    /**
     * The id getter when the class has the obvious one, null when a person has to pick.
     */
    public function findIdGetter(string $userClass): ?string
    {
        return null === $this->methodNameGuesser($userClass, 'getId') ? 'getId' : null;
    }

    private function methodNameGuesser(string $className, string $suspectedMethodName): ?array
    {
        $reflectionClass = new \ReflectionClass($className);

        if ($reflectionClass->hasMethod($suspectedMethodName)) {
            return null;
        }

        $classMethods = [];

        foreach ($reflectionClass->getMethods() as $method) {
            $classMethods[] = $method->name;
        }

        return $classMethods;
    }
}
