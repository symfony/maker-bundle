<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class FooTwigController extends AbstractController
{
    #[Route('/foo/twig', name: 'app_foo_twig')]
    public function index(): Response
    {
        return $this->render('foo_twig/index.html.twig', [
            'controller_name' => 'FooTwigController',
        ]);
    }
}
