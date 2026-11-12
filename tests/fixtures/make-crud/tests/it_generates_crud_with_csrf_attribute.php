<?php

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class GeneratedCrudControllerTest extends WebTestCase
{
    public function testDeleteAction()
    {
        $client = self::createClient();

        $crawler = $client->request('GET', '/sweet/food/new');
        $this->assertTrue($client->getResponse()->isSuccessful());

        $newForm = $crawler->selectButton('Save')->form();
        $client->submit($newForm, ['sweet_food[title]' => 'Candy']);
        $this->assertTrue($client->getResponse()->isRedirect());

        $crawler = $client->followRedirect();
        $this->assertStringContainsString('<td>Candy</td>', $client->getResponse()->getContent());

        $showUri = $crawler->filter('a:contains("show")')->eq(0)->link()->getUri();
        $crawler = $client->request('GET', $showUri);
        $this->assertTrue($client->getResponse()->isSuccessful());

        // a delete form with a wrong token deletes nothing and redirects to the index,
        // whether the controller checks the token itself or receives the InvalidCsrfTokenException
        $deleteForm = $crawler->selectButton('Delete')->form();
        $client->submit($deleteForm, ['_token' => 'wrong']);
        $this->assertTrue($client->getResponse()->isRedirect());

        $client->followRedirect();
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertStringContainsString('<td>Candy</td>', $client->getResponse()->getContent());

        // the delete form carries a valid token
        $crawler = $client->request('GET', $showUri);
        $deleteForm = $crawler->selectButton('Delete')->form();
        $client->submit($deleteForm);
        $this->assertTrue($client->getResponse()->isRedirect());

        $client->followRedirect();
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertStringContainsString('no records found', $client->getResponse()->getContent());
    }
}
