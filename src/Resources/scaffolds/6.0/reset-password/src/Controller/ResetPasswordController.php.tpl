<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RequestResetFormType;
use App\Form\ResetPasswordFormType;
use App\Repository\UserRepository;
use App\Security\LoginUser;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Header\TagHeader;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use SymfonyCasts\Bundle\ResetPassword\Controller\ResetPasswordControllerTrait;
use SymfonyCasts\Bundle\ResetPassword\Exception\ResetPasswordExceptionInterface;
use SymfonyCasts\Bundle\ResetPassword\Exception\TooManyPasswordRequestsException;
use SymfonyCasts\Bundle\ResetPassword\ResetPasswordHelperInterface;

#[Route('/reset-password')]
class ResetPasswordController extends AbstractController
{
    use ResetPasswordControllerTrait;

    public function __construct(
        private ResetPasswordHelperInterface $resetPasswordHelper,
        private UserRepository $userRepository,
    ) {
    }

    /**
     * Display & process form to request a password reset.
     */
    #[Route('', name: 'reset_password_request')]
    public function request(Request $request, MailerInterface $mailer): Response
    {
        $form = $this->createForm(RequestResetFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            return $this->processSendingPasswordResetEmail(
                $form->get('email')->getData(),
                $mailer
            );
        }

        return $this->render('reset_password/request.html.twig', [
            'requestForm' => $form->createView(),
        ]);
    }

    /**
     * Confirmation page after a user has requested a password reset.
     */
    #[Route('/check-email', name: 'reset_password_check_email')]
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
     */
    #[Route('/reset/{token}', name: 'reset_password')]
    public function reset(Request $request, LoginUser $login, UserPasswordHasherInterface $userPasswordHasher, string $token = null): Response
    {
        if ($token) {
            // We store the token in session and remove it from the URL, to avoid the URL being
            // loaded in a browser and potentially leaking the token to 3rd party JavaScript.
            $this->storeTokenInSession($token);

            return $this->redirectToRoute('reset_password');
        }

        if (null === $token = $this->getTokenFromSession()) {
            throw $this->createNotFoundException('No reset password token found in the URL or in the session.');
        }

        try {
            /** @var User $user */
            $user = $this->resetPasswordHelper->validateTokenAndFetchUser($token);
        } catch (ResetPasswordExceptionInterface $e) {
            $this->addFlash('error', $e->getReason());

            return $this->redirectToRoute('homepage');
        }

        // The token is valid; allow the user to change their password.
        $form = $this->createForm(ResetPasswordFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // A password reset token should be used only once, remove it.
            $this->resetPasswordHelper->removeResetRequest($token);

            // Encode(hash) the plain password, and set it.
            $user->setPassword($userPasswordHasher->hashPassword($user, $form->get('plainPassword')->getData()));

            $this->userRepository->save($user);

            // The session is cleaned up after the password has been changed.
            $this->cleanSessionAfterReset();

            // programmatic user login
            $login($user, $request);

            $this->addFlash('success', 'Your password was successfully reset, you are now logged in.');

            return $this->redirectToRoute('homepage');
        }

        return $this->render('reset_password/reset.html.twig', [
            'resetForm' => $form->createView(),
        ]);
    }

    private function processSendingPasswordResetEmail(string $emailFormData, MailerInterface $mailer): RedirectResponse
    {
        $user = $this->userRepository->findOneBy([
            'email' => $emailFormData,
        ]);

        // Do not reveal whether a user account was found or not.
        if (!$user) {
            return $this->redirectToRoute('reset_password_check_email');
        }

        try {
            $resetToken = $this->resetPasswordHelper->generateResetToken($user);
        } catch (TooManyPasswordRequestsException $e) {
            $this->addFlash('error', $e->getReason());

            return $this->redirectToRoute('homepage');
        } catch (ResetPasswordExceptionInterface $e) {
            // If you want to tell the user why a reset email was not sent, uncomment
            // the lines below and change the redirect to 'reset_password_request'.
            // Caution: This may reveal if a user is registered or not.
            //
            // $this->addFlash('reset_password_error', sprintf(
            //     '%s - %s',
            //     ResetPasswordExceptionInterface::MESSAGE_PROBLEM_HANDLE,
            //     $e->getReason()
            // ));

            return $this->redirectToRoute('reset_password_check_email');
        }

        $email = (new TemplatedEmail())
            ->from(new Address('webmaster@example.com', 'Webmaster')) // todo change email/name
            ->to(new Address($user->getEmail(), $user->getName()))
            ->subject('Your password reset request')
            ->htmlTemplate('reset_password/email.html.twig')
            ->context([
                'resetToken' => $resetToken,
            ])
        ;

        $email->getHeaders()
            ->add(new TagHeader('reset-password')) // https://symfony.com/doc/current/mailer.html#adding-tags-and-metadata-to-emails
            ->addTextHeader('X-CTA', $this->generateUrl( // helps with testing
                'reset_password',
                ['token' => $resetToken->getToken()],
                UrlGeneratorInterface::ABSOLUTE_URL
            ))
        ;

        $mailer->send($email);

        // Store the token object in session for retrieval in check-email route.
        $this->setTokenObjectInSession($resetToken);

        return $this->redirectToRoute('reset_password_check_email');
    }
}
