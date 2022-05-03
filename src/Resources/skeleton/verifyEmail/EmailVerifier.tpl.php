<?= "<?php\n" ?>

namespace <?= $namespace; ?>;

<?= $use_statements; ?>

class <?= $class_name; ?><?= "\n" ?>
{
    private <?= $use_typed_properties ? 'VerifyEmailHelperInterface ' : null ?>$verifyEmailHelper;
    private <?= $use_typed_properties ? 'MailerInterface ' : null ?>$mailer;
    private <?= $use_typed_properties ? 'EntityManagerInterface ' : null ?>$entityManager;

    public function __construct(VerifyEmailHelperInterface $helper, MailerInterface $mailer, EntityManagerInterface $manager)
    {
        $this->verifyEmailHelper = $helper;
        $this->mailer = $mailer;
        $this->entityManager = $manager;
    }

    public function sendEmailConfirmation(string $verifyEmailRouteName, UserInterface $user, TemplatedEmail $email): void
    {
        $signatureComponents = $this->verifyEmailHelper->generateSignature(
            $verifyEmailRouteName,
            $user-><?= $id_getter ?>(),
<?php if ($verify_email_anonymously): ?>
            $user-><?= $email_getter ?>(),
            ['id' => $user->getId()]
<?php else: ?>
            $user-><?= $email_getter ?>()
<?php endif; ?>
        );

        $context = $email->getContext();
        $context['signedUrl'] = $signatureComponents->getSignedUrl();
        $context['expiresAtMessageKey'] = $signatureComponents->getExpirationMessageKey();
        $context['expiresAtMessageData'] = $signatureComponents->getExpirationMessageData();

        $email->context($context);

        $this->mailer->send($email);
    }

    /**
     * @throws VerifyEmailExceptionInterface
     */
    public function handleEmailConfirmation(Request $request, UserInterface $user): void
    {
        $this->verifyEmailHelper->validateEmailConfirmation($request->getUri(), $user-><?= $id_getter ?>(), $user-><?= $email_getter?>());

        $user->setIsVerified(true);

        $this->entityManager->persist($user);
        $this->entityManager->flush();
    }
}
