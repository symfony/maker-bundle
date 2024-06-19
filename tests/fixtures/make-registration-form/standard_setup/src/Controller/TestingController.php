<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class TestingController extends AbstractController
{
    #[Route(path: '/', name: 'app_homepage')]
    public function homepage()
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        return new Response('Page Success');
    }

    #[Route(path: '/anonymous', name: 'app_anonymous')]
    public function anonymous()
    {
        return new Response('Page Success');
    }
}
