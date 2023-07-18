<?php

namespace App\Tests;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class GeneratedControllerTest extends WebTestCase
{
    public function testController()
    {
        $client = self::createClient();
        $client->request('GET', '/foo/standalone');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertSame('application/json', $client->getResponse()->headers->get('Content-Type'));
        $this->assertSame('{"message":"Welcome to your new controller!","path":"src\/Controller\/FooStandaloneController.php"}', $client->getResponse()->getContent());
    }

    public function testControllerInheritance()
    {
        $controller = $this->getContainer()->get('App\Controller\FooStandaloneController');
        $this->assertNotInstanceOf('Symfony\Bundle\FrameworkBundle\Controller\AbstractController', $controller);
    }
}
