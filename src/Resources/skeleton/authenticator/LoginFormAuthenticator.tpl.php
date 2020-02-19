<?= "<?php\n" ?>

namespace <?= $namespace ?>;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
<?= $user_needs_encoder ? "use Symfony\\Component\\Security\\Core\\Encoder\\UserPasswordEncoderInterface;\n" : null ?>
use Symfony\Component\Security\Core\Exception\InvalidCsrfTokenException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Guard\Authenticator\AbstractFormLoginAuthenticator;
<?= ($password_authenticated = $user_needs_encoder && interface_exists('Symfony\Component\Security\Guard\PasswordAuthenticatedInterface')) ? "use Symfony\Component\Security\Guard\PasswordAuthenticatedInterface;\n" : '' ?>
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class <?= $class_name; ?> extends AbstractFormLoginAuthenticator<?= $password_authenticated ? " implements PasswordAuthenticatedInterface\n" : "\n" ?>
{
    use TargetPathTrait;

    private $urlGenerator;
    private $csrfTokenManager;
<?= $user_needs_encoder ? "    private \$passwordEncoder;\n" : null ?>

    public function __construct(UrlGeneratorInterface $urlGenerator, CsrfTokenManagerInterface $csrfTokenManager<?= $user_needs_encoder ? ', UserPasswordEncoderInterface $passwordEncoder' : null ?>)
    {
        $this->urlGenerator = $urlGenerator;
        $this->csrfTokenManager = $csrfTokenManager;
<?= $user_needs_encoder ? "        \$this->passwordEncoder = \$passwordEncoder;\n" : null ?>
    }

    public function supports(Request $request)
    {
        return 'app_login' === $request->attributes->get('_route')
            && $request->isMethod('POST');
    }

    public function getCredentials(Request $request)
    {
        $credentials = [
            '<?= $username_field ?>' => $request->request->get('<?= $username_field ?>'),
            'password' => $request->request->get('password'),
            'csrf_token' => $request->request->get('_csrf_token'),
        ];
        $request->getSession()->set(
            Security::LAST_USERNAME,
            $credentials['<?= $username_field ?>']
        );

        return $credentials;
    }

    public function getUser($credentials, UserProviderInterface $userProvider)
    {
        $token = new CsrfToken('authenticate', $credentials['csrf_token']);
        if (!$this->csrfTokenManager->isTokenValid($token)) {
            throw new InvalidCsrfTokenException();
        }

        // Load / create our user however you need.
        // You can do this by calling the user provider, or with custom logic here.
        return $userProvider->loadUserByUsername($credentials['$username_field']);
    }

    public function checkCredentials($credentials, UserInterface $user)
    {
        <?= $user_needs_encoder ? "return \$this->passwordEncoder->isPasswordValid(\$user, \$credentials['password']);\n"
        : "// Check the user's password or other credentials and return true or false
        // If there are no credentials to check, you can just return true
        throw new \Exception('TODO: check the credentials inside '.__FILE__);\n" ?>
    }

<?php if ($password_authenticated): ?>
    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function getPassword($credentials): ?string
    {
        return $credentials['password'];
    }

<?php endif ?>
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey)
    {
        if ($targetPath = $this->getTargetPath($request->getSession(), $providerKey)) {
            return new RedirectResponse($targetPath);
        }

        // For example : return new RedirectResponse($this->urlGenerator->generate('some_route'));
        throw new \Exception('TODO: provide a valid redirect inside '.__FILE__);
    }

    protected function getLoginUrl()
    {
        return $this->urlGenerator->generate('app_login');
    }
}
