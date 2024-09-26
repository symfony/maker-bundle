<?php

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class GeneratedControllerTest extends WebTestCase
{
    public function testController()
    {
        $client = self::createClient();
        $client->request('GET', '/foo/twig');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertStringContainsString('<!DOCTYPE html>', $client->getResponse()->getContent());
        $this->assertStringContainsString('Hello FooTwigController', $client->getResponse()->getContent());
    }
}
