<?php

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class GeneratedControllerWithTwigTest extends WebTestCase
{
    public function testController()
    {
        $client = self::createClient();
        $client->request('GET', '/foo/twig');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertContains('<!DOCTYPE html>', $client->getResponse()->getContent());
        $this->assertContains('Hello FooTwigController', $client->getResponse()->getContent());
    }
}
