<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;

class MainController
{
    public function homepage()
    {
        // create a controller that will make the functional test pass
        return new Response('<html><body><h1>Hello World</h1></body></html>');
    }
}
