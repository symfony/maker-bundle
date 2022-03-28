<?php

namespace App\Controller\User;

use App\Entity\User;
use App\Form\User\ProfileFormType;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;

#[Route('/user', name: 'profile')]
class ProfileController extends AbstractController
{
    public function __invoke(Request $request, UserRepository $userRepository, ?UserInterface $user = null): Response
    {
        if (!$user) {
            throw $this->createAccessDeniedException();
        }

        if (!$user instanceof User) {
            throw new \LogicException('Invalid user type.');
        }

        $form = $this->createForm(ProfileFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $userRepository->save($user);
            $this->addFlash('success', 'You\'ve successfully updated your profile.');

            return $this->redirectToRoute('homepage');
        }

        return $this->render('user/profile.html.twig', [
            'profileForm' => $form->createView(),
        ]);
    }
}
