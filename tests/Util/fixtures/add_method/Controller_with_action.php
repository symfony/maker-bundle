<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class EmptyController
{
    /**
     * @Route("/action", name="app_action")
     */
    public function action(string $param): JsonResponse
    {
        return new JsonResponse(['param' => $param]);
    }
}
