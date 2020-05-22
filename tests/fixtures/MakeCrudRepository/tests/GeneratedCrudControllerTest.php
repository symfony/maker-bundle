<?php

namespace App\Tests;

use App\Entity\Product;
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
