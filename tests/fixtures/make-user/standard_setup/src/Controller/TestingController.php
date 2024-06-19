<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class TestingController extends AbstractController
{
    public function homepage()
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        return new Response('Homepage Success. Hello: '.$this->getUser()->getUserIdentifier());
    }
}
