<?php

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;

class GeneratedControllerTest extends WebTestCase
{
    public function testController()
    {
        $client = self::createClient();
        $client->request('GET', '/foo/standalone/twig');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertStringContainsString('<!DOCTYPE html>', $client->getResponse()->getContent());
        $this->assertStringContainsString('Hello FooStandaloneTwigController', $client->getResponse()->getContent());
    }

    public function testControllerInheritance()
    {
        $controller = $this->getContainer()->get('App\Controller\FooStandaloneTwigController');
        $this->assertNotInstanceOf('Symfony\Bundle\FrameworkBundle\Controller\AbstractController', $controller);
    }
}
