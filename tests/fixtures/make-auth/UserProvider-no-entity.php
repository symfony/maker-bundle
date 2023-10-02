<?php

namespace App\Security;

use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class UserProvider implements UserProviderInterface
{
    private $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function loadUserByUsername($username): UserInterface
    {
        return $this->loadUserByIdentifier($username);
    }

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        $user = (new User())
            ->setUserEmail($identifier);

        $user->setPassword($this->passwordHasher->hashPassword($user, 'passw0rd'));

        return $user;
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
    }

    public function supportsClass(string $class): bool
    {
    }
}
