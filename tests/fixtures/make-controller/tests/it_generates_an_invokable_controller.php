<?php

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class GeneratedControllerTest extends WebTestCase
{
    public function testControllerValidity()
    {
        $client = self::createClient();
        $client->request('GET', '/foo/invokable');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
    }

    public function testControllerInvokability()
    {
        $kernel = self::bootKernel();

        // templates may legitimately access app.request, so invoke the
        // controller the way it always runs: inside a request context
        $kernel->getContainer()->get('test.service_container')
            ->get('request_stack')
            ->push(\Symfony\Component\HttpFoundation\Request::create('/foo/invokable'));

        $controller = $kernel->getContainer()->get('App\Controller\FooInvokableController');
        $this->assertIsCallable($controller);

        $response = $controller();
        $this->assertInstanceOf('Symfony\Component\HttpFoundation\Response', $response);
    }
}
