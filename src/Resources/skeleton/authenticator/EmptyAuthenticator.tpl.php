<?= "<?php\n" ?>

namespace <?= $namespace ?>;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Guard\AbstractGuardAuthenticator;

/**
 * Class <?= $class_name ?>
 * @package <?= $namespace ?>
 */
class <?= $class_name ?> extends AbstractGuardAuthenticator
{
    /**
     * Does the authenticator support the given Request?
     * If this returns false, the authenticator will be skipped.
     *
     * @param Request $request
     * @return bool
     */
    public function supports(Request $request)
    {
        // TODO: implement function body
    }

    /**
     * Get the authentication credentials from the request and return them as any type.
     *
     * @param Request $request
     * @return mixed Any non-null value
     * @throws \UnexpectedValueException If null is returned
     */
    public function getCredentials(Request $request)
    {
        // TODO: implement function body
    }

    /**
     * Return a UserInterface object based on the credentials.
     *
     * @param mixed                 $credentials
     * @param UserProviderInterface $userProvider
     * @return UserInterface|null
     * @throws AuthenticationException
     */
    public function getUser($credentials, UserProviderInterface $userProvider)
    {
        // TODO: implement function body
    }

    /**
     * Returns true if the credentials are valid.
     *
     * @param mixed         $credentials
     * @param UserInterface $user
     * @return bool
     * @throws AuthenticationException
     */
    public function checkCredentials($credentials, UserInterface $user)
    {
        // TODO: implement function body
    }

    /**
     * Create an authenticated token for the given user.
     *
     * @param UserInterface $user
     * @param string        $providerKey The provider (i.e. firewall) key
     * @return GuardTokenInterface
     */
    public function createAuthenticatedToken(UserInterface $user, $providerKey)
    {
        // TODO: implement function body
    }

    /**
     * Called when authentication executed, but failed (e.g. wrong username password).
     *
     * @param Request                 $request
     * @param AuthenticationException $exception
     * @return Response|null
     */
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
    {
        // TODO: implement function body
    }

    /**
     * Called when authentication executed and was successful!
     *
     * @param Request        $request
     * @param TokenInterface $token
     * @param string         $providerKey The provider (i.e. firewall) key
     * @return Response|null
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey)
    {
        // TODO: implement function body
    }

    /**
     * Override to control what happens when the user hits a secure page
     *
     * @param Request $request
     * @param AuthenticationException|null $authException
     * @return RedirectResponse
     */
    public function start(Request $request, AuthenticationException $authException = null)
    {
        // TODO: implement function body
    }

    /**
     * Does this method support remember me cookies?
     *
     * @return bool
     */
    public function supportsRememberMe()
    {
        // TODO: implement function body
    }
}
