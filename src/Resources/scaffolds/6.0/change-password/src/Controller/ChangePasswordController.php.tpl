<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\ChangePasswordFormType;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;

#[Route('/user/change-password', name: 'change_password')]
class ChangePasswordController extends AbstractController
{
    public function __invoke(
        Request $request,
        UserPasswordHasherInterface $userPasswordHasher,
        UserRepository $userRepository,
        ?UserInterface $user = null,
    ): Response {
        if (!$user) {
            throw $this->createAccessDeniedException();
        }

        if (!$user instanceof User) {
            throw new \LogicException('Invalid user type.');
        }

        $form = $this->createForm(ChangePasswordFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // encode the plain password
            $user->setPassword(
                $userPasswordHasher->hashPassword(
                    $user,
                    $form->get('plainPassword')->getData()
                )
            );

            $userRepository->save($user);
            $this->addFlash('success', 'You\'ve successfully changed your password.');

            return $this->redirectToRoute('homepage');
        }

        return $this->render('user/change_password.html.twig', [
            'changePasswordForm' => $form->createView(),
        ]);
    }
}
