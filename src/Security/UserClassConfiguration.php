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

/**
 * Configuration about the user's new User class.
 *
 * @internal
 */
final class UserClassConfiguration
{
    private string $userProviderClass;

    public function __construct(
        private bool $isEntity,
        private string $identityPropertyName,
        private bool $hasPassword,
    ) {
    }

    public function isEntity(): bool
    {
        return $this->isEntity;
    }

    public function getIdentityPropertyName(): string
    {
        return $this->identityPropertyName;
    }

    public function hasPassword(): bool
    {
        return $this->hasPassword;
    }

    public function getUserProviderClass(): string
    {
        return $this->userProviderClass;
    }

    public function setUserProviderClass(string $userProviderClass): void
    {
        if ($this->isEntity()) {
            throw new \LogicException('No custom user class allowed for entity user.');
        }

        $this->userProviderClass = $userProviderClass;
    }
}
