<?php

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class GeneratedCrudControllerTest extends WebTestCase
{
    public function testIndexAction()
    {
        $client = self::createClient();
        $client->request('GET', '/sweet/food/');
        $this->assertTrue($client->getResponse()->isSuccessful());
    }
}
