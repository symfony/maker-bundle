<?php

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ConvertPhpServicesTest extends WebTestCase
{
    public function testIfServicesPhpFileExists ()
    {
        $client = self::createClient();
        $client->request('GET', '/');

        $this->assertEquals(200, $client->getResponse()->getStatusCode(), $client->getResponse()->getContent());
        $this->assertStringContainsString('IT WORKS', $client->getResponse()->getContent());
    }
}
