<?php

namespace App\Security;

use App\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;
use Symfony\Component\Security\Http\Authenticator\AuthenticatorInterface;

class LoginUser
{
    public function __construct(
        private UserAuthenticatorInterface $userAuthenticator,
        private AuthenticatorInterface $authenticator,
    ) {
    }

    public function __invoke(User $user, Request $request): void
    {
        $this->userAuthenticator->authenticateUser($user, $this->authenticator, $request);
    }
}
