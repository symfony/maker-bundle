<?= "<?php\n" ?>

namespace <?= $namespace ?>;

<?= $user_is_entity ? "use $user_fully_qualified_class_name;\n" : null ?>
<?= $user_is_entity ? "use Doctrine\\ORM\\EntityManagerInterface;\n" : null ?>
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
<?= $user_needs_encoder ? "use Symfony\\Component\\Security\\Core\\Encoder\\UserPasswordEncoderInterface;\n" : null ?>
use Symfony\Component\Security\Core\Exception\InvalidCsrfTokenException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Guard\Authenticator\AbstractFormLoginAuthenticator;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

/**
 * Class <?= $class_name ?>
 * @package <?= $namespace ?>
 */
class <?= $class_name; ?> extends AbstractFormLoginAuthenticator
{
<?= $user_is_entity ? "    /** @var EntityManagerInterface */\n    private \$entityManager;\n\n" : null ?>
    /** @var RouterInterface */
    private $router;

    /** @var CsrfTokenManagerInterface */
    private $csrfTokenManager;

<?= $user_needs_encoder ? "    /** @var UserPasswordEncoderInterface */\n    private \$passwordEncoder;\n" : null ?>

    /**
     * <?= $class_name; ?> constructor.
     *
     * @param EntityManagerInterface       $entityManager
     * @param RouterInterface              $router
     * @param CsrfTokenManagerInterface    $csrfTokenManager
     * @param UserPasswordEncoderInterface $passwordEncoder
     */
    public function __construct(<?= $user_is_entity ? 'EntityManagerInterface $entityManager, ' : null ?>RouterInterface $router, CsrfTokenManagerInterface $csrfTokenManager<?= $user_needs_encoder ? ', UserPasswordEncoderInterface $passwordEncoder' : null ?>)
    {
<?= $user_is_entity ? "        \$this->entityManager = \$entityManager;\n" : null ?>
        $this->router = $router;
        $this->csrfTokenManager = $csrfTokenManager;
<?= $user_needs_encoder ? "        \$this->passwordEncoder = \$passwordEncoder;\n" : null ?>
    }

    /**
     * Does the authenticator support the given Request?
     *
     * @param Request $request
     * @return bool
     */
    public function supports(Request $request)
    {
        return 'app_login' === $request->attributes->get('_route')
            && $request->isMethod('POST');
    }

    /**
     * Get the authentication credentials from the request and return them as any type (e.g. an associate array).
     *
     * @param Request $request
     * @return mixed Any non-null value
     * @throws \UnexpectedValueException If null is returned
     */
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

    /**
     * Return a UserInterface object based on the credentials.
     *
     * @param mixed                 $credentials
     * @param UserProviderInterface $userProvider
     * @return null|UserInterface
     * @throws \Exception
     * @throws InvalidCsrfTokenException
     */
    public function getUser($credentials, UserProviderInterface $userProvider)
    {
        $token = new CsrfToken('authenticate', $credentials['csrf_token']);
        if (!$this->csrfTokenManager->isTokenValid($token)) {
            throw new InvalidCsrfTokenException();
        }

        <?= $user_is_entity ? "return \$this->entityManager->getRepository($user_class_name::class)->findOneBy(['$username_field' => \$credentials['$username_field']]);\n"
        : "// Load / create our user however you need.
        // You can do this by calling the user provider, or with custom logic here.
        return \$userProvider->loadUserByUsername(\$credentials['$username_field']);\n"; ?>
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
        <?= $user_needs_encoder ? "return \$this->passwordEncoder->isPasswordValid(\$user, \$credentials['password']);\n"
        : "// Check the user's password or other credentials and return true or false
        // If there are no credentials to check, you can just return true
        throw new \Exception('TODO: check the credentials inside '.__FILE__);\n" ?>
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
        if ($targetPath = $this->getTargetPath($request->getSession(), $providerKey)) {
            return new RedirectResponse($targetPath);
        }

        // For example : return new RedirectResponse($this->router->generate('some_route'));
        throw new \Exception('TODO: provide a valid redirect inside '.__FILE__);
    }

    /**
     * Return the URL to the login page.
     *
     * @return string
     */
    protected function getLoginUrl()
    {
        return $this->router->generate('app_login');
    }
}
