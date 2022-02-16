<?= "<?php\n" ?>

namespace <?= $namespace ?>;

<?= $use_statements; ?>

<?php if ($use_attributes) { ?>
#[Route('/reset-password')]
<?php } else { ?>
/**
 * @Route("/reset-password")
 */
<?php } ?>
class <?= $class_name ?> extends AbstractController
{
    use ResetPasswordControllerTrait;

    private $resetPasswordHelper;
    private $entityManager;

    public function __construct(ResetPasswordHelperInterface $resetPasswordHelper, EntityManagerInterface $entityManager)
    {
        $this->resetPasswordHelper = $resetPasswordHelper;
        $this->entityManager = $entityManager;
    }

    /**
     * Display & process form to request a password reset.
<?php if ($use_attributes) { ?>
     */
    #[Route('', name: 'app_forgot_password_request')]
<?php } else { ?>
     *
     * @Route("", name="app_forgot_password_request")
     */
<?php } ?>
    public function request(Request $request, MailerInterface $mailer<?php if ($translator_available): ?>, TranslatorInterface $translator<?php endif ?>): Response
    {
        $form = $this->createForm(<?= $request_form_type_class_name ?>::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            return $this->processSendingPasswordResetEmail(
                $form->get('<?= $email_field ?>')->getData(),
                $mailer<?php if ($translator_available): ?>,
                $translator<?php endif ?><?= "\n" ?>
            );
        }

        return $this->render('reset_password/request.html.twig', [
            'requestForm' => $form->createView(),
        ]);
    }

    /**
     * Confirmation page after a user has requested a password reset.
<?php if ($use_attributes) { ?>
     */
    #[Route('/check-email', name: 'app_check_email')]
<?php } else { ?>
     *
     * @Route("/check-email", name="app_check_email")
     */
<?php } ?>
    public function checkEmail(): Response
    {
        // Generate a fake token if the user does not exist or someone hit this page directly.
        // This prevents exposing whether or not a user was found with the given email address or not
        if (null === ($resetToken = $this->getTokenObjectFromSession())) {
            $resetToken = $this->resetPasswordHelper->generateFakeResetToken();
        }

        return $this->render('reset_password/check_email.html.twig', [
            'resetToken' => $resetToken,
        ]);
    }

    /**
     * Validates and process the reset URL that the user clicked in their email.
<?php if ($use_attributes) { ?>
     */
    #[Route('/reset/{token}', name: 'app_reset_password')]
<?php } else { ?>
     *
     * @Route("/reset/{token}", name="app_reset_password")
     */
<?php } ?>
    public function reset(Request $request, <?= $password_hasher_class_details->getShortName() ?> <?= $password_hasher_variable_name ?><?php if ($translator_available): ?>, TranslatorInterface $translator<?php endif ?>, string $token = null): Response
    {
        if ($token) {
            // We store the token in session and remove it from the URL, to avoid the URL being
            // loaded in a browser and potentially leaking the token to 3rd party JavaScript.
            $this->storeTokenInSession($token);

            return $this->redirectToRoute('app_reset_password');
        }

        $token = $this->getTokenFromSession();
        if (null === $token) {
            throw $this->createNotFoundException('No reset password token found in the URL or in the session.');
        }

        try {
            $user = $this->resetPasswordHelper->validateTokenAndFetchUser($token);
        } catch (ResetPasswordExceptionInterface $e) {
            $this->addFlash('reset_password_error', sprintf(
                '%s - %s',
                <?php if ($translator_available): ?>$translator->trans(<?= $problem_validate_message_or_constant ?>, [], 'ResetPasswordBundle')<?php else: ?><?= $problem_validate_message_or_constant ?><?php endif ?>,
                <?php if ($translator_available): ?>$translator->trans($e->getReason(), [], 'ResetPasswordBundle')<?php else: ?>$e->getReason()<?php endif ?><?= "\n" ?>
            ));

            return $this->redirectToRoute('app_forgot_password_request');
        }

        // The token is valid; allow the user to change their password.
        $form = $this->createForm(<?= $reset_form_type_class_name ?>::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // A password reset token should be used only once, remove it.
            $this->resetPasswordHelper->removeResetRequest($token);

            // Encode(hash) the plain password, and set it.
            $encodedPassword = <?= $password_hasher_variable_name ?>-><?= $use_password_hasher ? 'hashPassword' : 'encodePassword' ?>(
                $user,
                $form->get('plainPassword')->getData()
            );

            $user-><?= $password_setter ?>($encodedPassword);
            $this->entityManager->flush();

            // The session is cleaned up after the password has been changed.
            $this->cleanSessionAfterReset();

            return $this->redirectToRoute('<?= $success_redirect_route ?>');
        }

        return $this->render('reset_password/reset.html.twig', [
            'resetForm' => $form->createView(),
        ]);
    }

    private function processSendingPasswordResetEmail(string $emailFormData, MailerInterface $mailer<?php if ($translator_available): ?>, TranslatorInterface $translator<?php endif ?>): RedirectResponse
    {
        $user = $this->entityManager->getRepository(<?= $user_class_name ?>::class)->findOneBy([
            '<?= $email_field ?>' => $emailFormData,
        ]);

        // Do not reveal whether a user account was found or not.
        if (!$user) {
            return $this->redirectToRoute('app_check_email');
        }

        try {
            $resetToken = $this->resetPasswordHelper->generateResetToken($user);
        } catch (ResetPasswordExceptionInterface $e) {
            // If you want to tell the user why a reset email was not sent, uncomment
            // the lines below and change the redirect to 'app_forgot_password_request'.
            // Caution: This may reveal if a user is registered or not.
            //
            // $this->addFlash('reset_password_error', sprintf(
            //     '%s - %s',
            //     <?php if ($translator_available): ?>$translator->trans(<?= $problem_handle_message_or_constant ?>, [], 'ResetPasswordBundle')<?php else: ?><?= $problem_handle_message_or_constant ?><?php endif ?>,
            //     <?php if ($translator_available): ?>$translator->trans($e->getReason(), [], 'ResetPasswordBundle')<?php else: ?>$e->getReason()<?php endif ?><?= "\n" ?>
            // ));

            return $this->redirectToRoute('app_check_email');
        }

        $email = (new TemplatedEmail())
            ->from(new Address('<?= $from_email ?>', '<?= $from_email_name ?>'))
            ->to($user-><?= $email_getter ?>())
            ->subject('Your password reset request')
            ->htmlTemplate('reset_password/email.html.twig')
            ->context([
                'resetToken' => $resetToken,
            ])
        ;

        $mailer->send($email);

        // Store the token object in session for retrieval in check-email route.
        $this->setTokenObjectInSession($resetToken);

        return $this->redirectToRoute('app_check_email');
    }
}
