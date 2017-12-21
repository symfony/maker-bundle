<?php

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class GeneratedControllerTest extends WebTestCase
{
    public function testController()
    {
        $client = self::createClient();
        $client->request('GET', '/foo/bar');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
    }
}
