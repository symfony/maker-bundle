<?= "<?php" . PHP_EOL ?>

namespace <?= $namespace ?>;

<?php if($user_is_entity): ?>
use <?= $user_fully_qualified_class_name ?>;
use Doctrine\ORM\EntityManagerInterface;
<? endif ?>
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
<?= $user_needs_encoder ? "use Symfony\\Component\\Security\\Core\\Encoder\\UserPasswordEncoderInterface;" . PHP_EOL : null ?>
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
<?= $user_is_entity ? "    /** @var EntityManagerInterface */" . PHP_EOL . "    private \$entityManager;" . PHP_EOL . PHP_EOL : null ?>
    /** @var RouterInterface */
    private $router;

    /** @var CsrfTokenManagerInterface */
    private $csrfTokenManager;

<?= $user_needs_encoder ? "    /** @var UserPasswordEncoderInterface */" . PHP_EOL . "    private \$passwordEncoder;" . PHP_EOL : null ?>

    /**
     * <?= $class_name; ?> constructor.
     *
<?= $user_is_entity ? "     * @param EntityManagerInterface       \$entityManager" . PHP_EOL : null ?>
     * @param RouterInterface              $router
     * @param CsrfTokenManagerInterface    $csrfTokenManager
<?= $user_needs_encoder ? "     * @param UserPasswordEncoderInterface \$passwordEncoder" . PHP_EOL : null ?>
     */
    public function __construct(<?= $user_is_entity ? 'EntityManagerInterface $entityManager, ' : null ?>RouterInterface $router, CsrfTokenManagerInterface $csrfTokenManager<?= $user_needs_encoder ? ', UserPasswordEncoderInterface $passwordEncoder' : null ?>)
    {
<?= $user_is_entity ? "        \$this->entityManager = \$entityManager;" . PHP_EOL : null ?>
        $this->router = $router;
        $this->csrfTokenManager = $csrfTokenManager;
<?= $user_needs_encoder ? "        \$this->passwordEncoder = \$passwordEncoder;" . PHP_EOL : null ?>
    }

    /**
     * {@inheritdoc}
     */
    public function supports(Request $request): bool
    {
        return 'app_login' === $request->attributes->get('_route')
            && $request->isMethod('POST');
    }

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
     */
    public function getUser($credentials, UserProviderInterface $userProvider)
    {
        $token = new CsrfToken('authenticate', $credentials['csrf_token']);
        if (!$this->csrfTokenManager->isTokenValid($token)) {
            throw new InvalidCsrfTokenException();
        }

        <?= $user_is_entity ? "return \$this->entityManager->getRepository($user_class_name::class)->findOneBy(['$username_field' => \$credentials['$username_field']]);" . PHP_EOL
        : "// Load / create our user however you need.
        // You can do this by calling the user provider, or with custom logic here.
        return \$userProvider->loadUserByUsername(\$credentials['$username_field']);" . PHP_EOL; ?>
    }

    /**
     * {@inheritdoc}
     */
    public function checkCredentials($credentials, UserInterface $user): bool
    {
        <?= $user_needs_encoder ? "return \$this->passwordEncoder->isPasswordValid(\$user, \$credentials['password']);" . PHP_EOL
        : "// Check the user's password or other credentials and return true or false
        // If there are no credentials to check, you can just return true
        throw new \Exception('TODO: check the credentials inside '.__FILE__);" . PHP_EOL ?>
    }

    /**
     * {@inheritdoc}
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
     * @return string
     */
    protected function getLoginUrl(): string
    {
        return $this->router->generate('app_login');
    }
}
