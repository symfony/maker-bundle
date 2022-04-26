<?= "<?php\n" ?>

namespace <?= $namespace ?>;

<?= $use_statements; ?>

class <?= $class_name; ?> extends AbstractLoginFormAuthenticator
{
    use TargetPathTrait;

    public const LOGIN_ROUTE = 'app_login';

    private <?= $use_typed_properties ? 'UrlGeneratorInterface ' : null ?>$urlGenerator;

    public function __construct(UrlGeneratorInterface $urlGenerator)
    {
        $this->urlGenerator = $urlGenerator;
    }

    public function authenticate(Request $request): Passport
    {
        $<?= $username_field_var ?> = $request->request->get('<?= $username_field ?>', '');

        $request->getSession()->set(Security::LAST_USERNAME, $<?= $username_field_var ?>);

        return new Passport(
            new UserBadge($<?= $username_field_var ?>),
            new PasswordCredentials($request->request->get('password', '')),
            [
                new CsrfTokenBadge('authenticate', $request->request->get('_csrf_token')),
            ]
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        if ($targetPath = $this->getTargetPath($request->getSession(), $firewallName)) {
            return new RedirectResponse($targetPath);
        }

        // For example:
        // return new RedirectResponse($this->urlGenerator->generate('some_route'));
        throw new \Exception('TODO: provide a valid redirect inside '.__FILE__);
    }

    protected function getLoginUrl(Request $request): string
    {
        return $this->urlGenerator->generate(self::LOGIN_ROUTE);
    }
}
