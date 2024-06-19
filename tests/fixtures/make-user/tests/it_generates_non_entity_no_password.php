<?php

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class GeneratedUserTest extends WebTestCase
{
    public function testGeneratedEntity()
    {
        // login then access a protected page
        $client = self::createClient();
        $client->request('GET', '/login?username=hal9000');

        $this->assertSame(302, $client->getResponse()->getStatusCode());
        $client->followRedirect();
        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $this->assertSame('Homepage Success. Hello: hal9000', $client->getResponse()->getContent());
    }
}
