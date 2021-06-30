<?= "<?php\n" ?>

namespace <?= $namespace; ?>;

<?= $use_statements; ?>

class <?= $class_name; ?> extends <?= $parent_class_name; ?><?= "\n" ?>
{
<?php if ($will_verify_email): ?>
    private $emailVerifier;

    public function __construct(EmailVerifier $emailVerifier)
    {
        $this->emailVerifier = $emailVerifier;
    }

<?php endif; ?>
<?php if ($use_attributes) { ?>
    #[Route('<?= $route_path ?>', name: '<?= $route_name ?>')]
<?php } else { ?>
    /**
     * @Route("<?= $route_path ?>", name="<?= $route_name ?>")
     */
<?php } ?>
    public function register(Request $request, UserPasswordEncoderInterface $passwordEncoder<?= $authenticator_full_class_name ? sprintf(', GuardAuthenticatorHandler $guardHandler, %s $authenticator', $authenticator_class_name) : '' ?>): Response
    {
        $user = new <?= $user_class_name ?>();
        $form = $this->createForm(<?= $form_class_name ?>::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // encode the plain password
            $user->set<?= ucfirst($password_field) ?>(
                $passwordEncoder->encodePassword(
                    $user,
                    $form->get('plainPassword')->getData()
                )
            );

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($user);
            $entityManager->flush();
<?php if ($will_verify_email): ?>

            // generate a signed url and email it to the user
            $this->emailVerifier->sendEmailConfirmation('app_verify_email', $user,
                (new TemplatedEmail())
                    ->from(new Address('<?= $from_email ?>', '<?= $from_email_name ?>'))
                    ->to($user-><?= $email_getter ?>())
                    ->subject('Please Confirm your Email')
                    ->htmlTemplate('registration/confirmation_email.html.twig')
            );
<?php endif; ?>
            // do anything else you need here, like send an email

<?php if ($authenticator_full_class_name): ?>
            return $guardHandler->authenticateUserAndHandleSuccess(
                $user,
                $request,
                $authenticator,
                '<?= $firewall_name; ?>' // firewall name in security.yaml
            );
<?php else: ?>
            return $this->redirectToRoute('<?= $redirect_route_name ?>');
<?php endif; ?>
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }
<?php if ($will_verify_email): ?>

<?php if ($use_attributes) { ?>
    #[Route('/verify/email', name: 'app_verify_email')]
<?php } else { ?>
    /**
     * @Route("/verify/email", name="app_verify_email")
     */
<?php } ?>
    public function verifyUserEmail(Request $request<?= $verify_email_anonymously ? sprintf(', %s %s', $repository_class_name, $repository_var) : null ?>): Response
    {
<?php if (!$verify_email_anonymously): ?>
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
<?php else: ?>
        $id = $request->get('id');

        if (null === $id) {
            return $this->redirectToRoute('app_register');
        }
<?php if ('$manager' === $repository_var): ?>

        $repository = $manager->getRepository(<?= $user_class_name ?>::class);
        $user = $repository->find($id);
<?php else: ?>

        $user = <?= $repository_var; ?>->find($id);
<?php endif; ?>

        if (null === $user) {
            return $this->redirectToRoute('app_register');
        }
<?php endif; ?>

        // validate email confirmation link, sets User::isVerified=true and persists
        try {
            $this->emailVerifier->handleEmailConfirmation($request, <?= $verify_email_anonymously ? '$user' : '$this->getUser()' ?>);
        } catch (VerifyEmailExceptionInterface $exception) {
            $this->addFlash('verify_email_error', $exception->getReason());

            return $this->redirectToRoute('<?= $route_name ?>');
        }

        // @TODO Change the redirect on success and handle or remove the flash message in your templates
        $this->addFlash('success', 'Your email address has been verified.');

        return $this->redirectToRoute('app_register');
    }
<?php endif; ?>
}
