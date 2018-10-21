<?= "<?php" . PHP_EOL ?>

namespace <?= $namespace ?>;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Guard\AbstractGuardAuthenticator;
use Symfony\Component\Security\Guard\Token\GuardTokenInterface;

class <?= $class_name ?> extends AbstractGuardAuthenticator
{
    /**
     * {@inheritdoc}
     */
    public function supports(Request $request): bool
    {
        // TODO: implement method
    }

    /**
     * {@inheritdoc}
     */
    public function getCredentials(Request $request)
    {
        // TODO: implement method
    }

    /**
     * {@inheritdoc}
     */
    public function getUser($credentials, UserProviderInterface $userProvider)
    {
        // TODO: implement method
    }

    /**
     * {@inheritdoc}
     */
    public function checkCredentials($credentials, UserInterface $user): bool
    {
        // TODO: implement method
    }

    /**
     * {@inheritdoc}
     */
    public function createAuthenticatedToken(UserInterface $user, $providerKey): GuardTokenInterface
    {
        // TODO: implement method
    }

    /**
     * {@inheritdoc}
     */
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
    {
        // TODO: implement method
    }

    /**
     * {@inheritdoc}
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey)
    {
        // TODO: implement method
    }

    /**
     * {@inheritdoc}
     */
    public function start(Request $request, AuthenticationException $authException = null)
    {
        // TODO: implement method
    }

    /**
     * {@inheritdoc}
     */
    public function supportsRememberMe(): bool
    {
        // TODO: implement method
    }
}
