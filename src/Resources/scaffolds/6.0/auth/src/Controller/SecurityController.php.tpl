<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    #[Route(path: '/login', name: 'login')]
    public function login(AuthenticationUtils $authenticationUtils, Request $request): Response
    {
        if ($this->isGranted('IS_AUTHENTICATED_FULLY')) {
            return new RedirectResponse($request->query->get('target', $this->generateUrl('homepage')));
        }

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();

        // current user's username (if already logged in) or last username entered by the user
        $lastUsername = $this->getUser()?->getUserIdentifier() ?? $authenticationUtils->getLastUsername();

        return $this->render('login.html.twig', ['last_username' => $lastUsername, 'error' => $error]);
    }

    #[Route(path: '/logout', name: 'logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}
