<?php

namespace App\Controller;

use App\Services\FakeService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class ConvertServicesTestController extends AbstractController
{
    public function homepage(FakeService $fakeService)
    {
        return new Response($fakeService->getText());
    }
}
