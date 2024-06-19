<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class FixtureController extends AbstractController
{
    #[Route(name: 'app_home')]
    public function index(): JsonResponse
    {
        return $this->json(['message' => 'success']);
    }
}
