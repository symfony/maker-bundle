<?php

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class GeneratedReactTest extends WebTestCase
{
    public function testReactApp()
    {
        $client = self::createClient();
        $client->request('GET', '/app-react');

        // dump($client->getResponse()->getStatusCode());

        // $this->assertEquals(200, $client->getResponse()->getStatusCode());
    }
}
