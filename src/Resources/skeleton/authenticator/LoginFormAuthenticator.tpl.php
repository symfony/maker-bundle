<?= "<?php\n" ?>

namespace <?= $namespace ?>;

<?= $use_statements; ?>

<?= $user_is_entity ? "use $user_fully_qualified_class_name;\n" : null ?>
<?= $user_is_entity ? "use Doctrine\\ORM\\EntityManagerInterface;\n" : null ?>
<?= ($user_needs_encoder && str_contains($password_encoder_details->getUseStatement() , 'Hasher')) ? $password_encoder_details->getUseStatement() : null ?>
<?= ($user_needs_encoder && str_contains($password_encoder_details->getUseStatement() , 'Encoder')) ? $password_encoder_details->getUseStatement() : null ?>
<?= $user_needs_encoder ? "use Symfony\\Component\\Security\\Core\\Encoder\\UserPasswordEncoderInterface;\n" : null ?>
<?= ($password_authenticated = $user_needs_encoder && interface_exists('Symfony\Component\Security\Guard\PasswordAuthenticatedInterface')) ? "use Symfony\Component\Security\Guard\PasswordAuthenticatedInterface;\n" : '' ?>

class <?= $class_name; ?> extends AbstractFormLoginAuthenticator<?= $password_authenticated ? " implements PasswordAuthenticatedInterface\n" : "\n" ?>
{
    use TargetPathTrait;

    public const LOGIN_ROUTE = 'app_login';

<?= $user_is_entity ? "    private \$entityManager;\n" : null ?>
    private $urlGenerator;
    private $csrfTokenManager;
<?= $user_needs_encoder ? $password_encoder_details->getPropertyStatement() : null ?>

    public function __construct(<?= $user_is_entity ? 'EntityManagerInterface $entityManager, ' : null ?>UrlGeneratorInterface $urlGenerator, CsrfTokenManagerInterface $csrfTokenManager<?= $user_needs_encoder ? sprintf(', %s', $password_encoder_details->getMethodArgument()) : null ?>)
    {
<?= $user_is_entity ? "        \$this->entityManager = \$entityManager;\n" : null ?>
        $this->urlGenerator = $urlGenerator;
        $this->csrfTokenManager = $csrfTokenManager;
<?= $user_needs_encoder ? $password_encoder_details->getConstructorArgument() : null ?>
    }

    public function supports(Request $request)
    {
        return self::LOGIN_ROUTE === $request->attributes->get('_route')
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
            throw new UsernameNotFoundException('<?= ucfirst($username_field_label) ?> could not be found.');
        }

        return $user;
    }

    public function checkCredentials($credentials, UserInterface $user)
    {
<?php if ($user_needs_encoder): ?>
        return <?= $password_encoder_details->getProperty() ?>->isPasswordValid($user, $credentials['password']);
<?php else: ?>
        // Check the user's password or other credentials and return true or false
        // If there are no credentials to check, you can just return true
        throw new \Exception('TODO: check the credentials inside '.__FILE__);
<?php endif; ?>
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
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, <?= $provider_key_type_hint ?>$providerKey)
    {
        if ($targetPath = $this->getTargetPath($request->getSession(), $providerKey)) {
            return new RedirectResponse($targetPath);
        }

        // For example : return new RedirectResponse($this->urlGenerator->generate('some_route'));
        throw new \Exception('TODO: provide a valid redirect inside '.__FILE__);
    }

    protected function getLoginUrl()
    {
        return $this->urlGenerator->generate(self::LOGIN_ROUTE);
    }
}
