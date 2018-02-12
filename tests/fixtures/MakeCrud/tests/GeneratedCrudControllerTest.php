<?php

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class GeneratedCrudControllerTest extends WebTestCase
{
    public function testIndexAction()
    {
        $client = self::createClient();
        $client->request('GET', '/sweet/food/');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertContains('<!DOCTYPE html>', $client->getResponse()->getContent());
        $this->assertContains('SweetFood index', $client->getResponse()->getContent());
    }

    public function testNewAction()
    {
        $client = self::createClient();
        $client->request('GET', '/sweet/food/new');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertContains('<!DOCTYPE html>', $client->getResponse()->getContent());
        $this->assertContains('New SweetFood', $client->getResponse()->getContent());
    }
}
