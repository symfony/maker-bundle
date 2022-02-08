<?php echo "<?php\n" ?>

namespace <?php echo $namespace ?>;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use <?= $use_legacy_passport_interface ? 'Symfony\\Component\\Security\\Http\\Authenticator\\Passport\\PassportInterface' : 'Symfony\\Component\\Security\\Http\\Authenticator\\Passport\\Passport' ?>;

class <?php echo $class_name ?> extends AbstractAuthenticator
{
    public function supports(Request $request): ?bool
    {
        // TODO: Implement supports() method.
    }

    public function authenticate(Request $request): <?= ($use_legacy_passport_interface ? 'PassportInterface' : 'Passport') . "\n" ?>
    {
        // TODO: Implement authenticate() method.
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        // TODO: Implement onAuthenticationSuccess() method.
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        // TODO: Implement onAuthenticationFailure() method.
    }

//    public function start(Request $request, AuthenticationException $authException = null): Response
//    {
//        /*
//         * If you would like this class to control what happens when an anonymous user accesses a
//         * protected page (e.g. redirect to /login), uncomment this method and make this class
//         * implement Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface.
//         *
//         * For more details, see https://symfony.com/doc/current/security/experimental_authenticators.html#configuring-the-authentication-entry-point
//         */
//    }
}
