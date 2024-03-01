<?= "<?php\n" ?>

namespace <?= $namespace; ?>;

<?= $use_statements; ?>

class <?= $class_name; ?> extends AbstractController
{
<?php if ($will_verify_email): ?>
    private <?= $generator->getPropertyType($email_verifier_class_details) ?>$emailVerifier;

    public function __construct(<?= $email_verifier_class_details->getShortName() ?> $emailVerifier)
    {
        $this->emailVerifier = $emailVerifier;
    }

<?php endif; ?>
<?= $generator->generateRouteForControllerMethod($route_path, $route_name) ?>
    public function register(Request $request, UserPasswordHasherInterface $userPasswordHasher<?= $login_after_registration ? ', Security $security': '' ?>, EntityManagerInterface $entityManager): Response
    {
        $user = new <?= $user_class_name ?>();
        $form = $this->createForm(<?= $form_class_name ?>::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // encode the plain password
            $user->set<?= ucfirst($password_field) ?>(
                    $userPasswordHasher->hashPassword(
                    $user,
                    $form->get('plainPassword')->getData()
                )
            );

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

<?php if ($login_after_registration): ?>
            return $security->login($user, <?= $authenticator ?>, '<?= $firewall ?>');
<?php else: ?>
            return $this->redirectToRoute('<?= $redirect_route_name ?>');
<?php endif; ?>
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form,
        ]);
    }
<?php if ($will_verify_email): ?>

<?= $generator->generateRouteForControllerMethod('/verify/email', 'app_verify_email') ?>
    public function verifyUserEmail(Request $request<?php if ($translator_available): ?>, TranslatorInterface $translator<?php endif ?><?= $verify_email_anonymously ? sprintf(', %s %s', $repository_class_name, $repository_var) : null ?>): Response
    {
<?php if (!$verify_email_anonymously): ?>
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
<?php else: ?>
        $id = $request->query->get('id');

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
            $this->addFlash('verify_email_error', <?php if ($translator_available): ?>$translator->trans($exception->getReason(), [], 'VerifyEmailBundle')<?php else: ?>$exception->getReason()<?php endif ?>);

            return $this->redirectToRoute('<?= $route_name ?>');
        }

        // @TODO Change the redirect on success and handle or remove the flash message in your templates
        $this->addFlash('success', 'Your email address has been verified.');

        return $this->redirectToRoute('app_register');
    }
<?php endif; ?>
}
