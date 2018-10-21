<?= "<?php\n" ?>

namespace <?= $namespace ?>;

<?= $user_is_entity ? "use $user_fully_qualified_class_name;\n" : null ?>
<?= $user_is_entity ? "use Doctrine\\ORM\\EntityManagerInterface;\n" : null ?>
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
<?= $user_needs_encoder ? "use Symfony\\Component\\Security\\Core\\Encoder\\UserPasswordEncoderInterface;\n" : null ?>
use Symfony\Component\Security\Core\Exception\InvalidCsrfTokenException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Guard\Authenticator\AbstractFormLoginAuthenticator;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class <?= $class_name; ?> extends AbstractFormLoginAuthenticator
{
    use TargetPathTrait;

<?= $user_is_entity ? "    private \$entityManager;\n" : null ?>
    private $router;
    private $csrfTokenManager;
<?= $user_needs_encoder ? "    private \$passwordEncoder;\n" : null ?>

    public function __construct(<?= $user_is_entity ? 'EntityManagerInterface $entityManager, ' : null ?>RouterInterface $router, CsrfTokenManagerInterface $csrfTokenManager<?= $user_needs_encoder ? ', UserPasswordEncoderInterface $passwordEncoder' : null ?>)
    {
<?= $user_is_entity ? "        \$this->entityManager = \$entityManager;\n" : null ?>
        $this->router = $router;
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

        <?= $user_is_entity ? "\$user = \$this->entityManager->getRepository($user_class_name::class)->findOneBy(['$username_field' => \$credentials['$username_field']]);\n"
        : "// Load / create our user however you need.
        // You can do this by calling the user provider, or with custom logic here.
        \$user = \$userProvider->loadUserByUsername(\$credentials['$username_field']);\n"; ?>

        if (!$user) {
            // fail authentication with a custom error
            throw new CustomUserMessageAuthenticationException('<?= ucfirst($username_field_label) ?> could not be found.');
        }

        return $user;
    }

    public function checkCredentials($credentials, UserInterface $user)
    {
        <?= $user_needs_encoder ? "return \$this->passwordEncoder->isPasswordValid(\$user, \$credentials['password']);\n"
        : "// Check the user's password or other credentials and return true or false
        // If there are no credentials to check, you can just return true
        throw new \Exception('TODO: check the credentials inside '.__FILE__);\n" ?>
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey)
    {
        if ($targetPath = $this->getTargetPath($request->getSession(), $providerKey)) {
            return new RedirectResponse($targetPath);
        }

        // For example : return new RedirectResponse($this->router->generate('some_route'));
        throw new \Exception('TODO: provide a valid redirect inside '.__FILE__);
    }

    protected function getLoginUrl()
    {
        return $this->router->generate('app_login');
    }
}
