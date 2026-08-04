<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

class FixtureController extends AbstractController
{
    public function __construct(
        public UserPasswordHasherInterface $passwordHasher
    ) {
    }

    #[Route(name: 'app_index')]
    public function index(): Response
    {
        return $this->render('base.html.twig');
    }
}
