<?php

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class GeneratedCrudControllerTest extends WebTestCase
{
    public function testIndexAction()
    {
        $client = self::createClient();
        $crawler = $client->request('GET', '/sweet/food/');
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertContains('<!DOCTYPE html>', $client->getResponse()->getContent());
        $this->assertContains('SweetFood index', $client->getResponse()->getContent());

        $newLink = $crawler->filter('a:contains("Create new")')->eq(0)->link();

        $crawler = $client->click($newLink);
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertContains('<!DOCTYPE html>', $client->getResponse()->getContent());
        $this->assertContains('New SweetFood', $client->getResponse()->getContent());

        $newForm = $crawler->selectButton('Save')->form();
        $client->submit($newForm, ['sweet_food[title]' => 'Candy']);
        $this->assertTrue($client->getResponse()->isRedirect());

        $crawler = $client->followRedirect();
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertContains('<!DOCTYPE html>', $client->getResponse()->getContent());
        $this->assertContains('SweetFood index', $client->getResponse()->getContent());
        $this->assertContains('<td>Candy</td>', $client->getResponse()->getContent());

        $editLink = $crawler->filter('a:contains("edit")')->eq(0)->link();
        $crawler = $client->click($editLink);
        $this->assertContains('<!DOCTYPE html>', $client->getResponse()->getContent());
        $this->assertContains('Edit SweetFood', $client->getResponse()->getContent());
        $this->assertGreaterThan(0, $crawler->filter('input[type=text]')->count());

        $editForm = $crawler->selectButton('Update')->form();
        $client->submit($editForm, ['sweet_food[title]' => 'Candy edited']);
        $this->assertTrue($client->getResponse()->isRedirect());

        $crawler = $client->followRedirect();
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertContains('<!DOCTYPE html>', $client->getResponse()->getContent());
        $this->assertContains('Edit SweetFood', $client->getResponse()->getContent());
        $this->assertGreaterThan(0, $crawler->filter('input[type=text]')->count());
        $this->assertEquals('Candy edited', $crawler->filter('input[type=text]')->attr('value'));

        $backTolistLink = $crawler->filter('a:contains("back to list")')->eq(0)->link();

        $crawler = $client->click($backTolistLink);
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertContains('<!DOCTYPE html>', $client->getResponse()->getContent());
        $this->assertContains('SweetFood index', $client->getResponse()->getContent());
        $this->assertContains('Candy edited', $client->getResponse()->getContent());

        $showLink = $crawler->filter('a:contains("show")')->eq(0)->link();

        $crawler = $client->click($showLink);
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertContains('<!DOCTYPE html>', $client->getResponse()->getContent());
        $this->assertContains('SweetFood', $client->getResponse()->getContent());
        $this->assertContains('Candy edited', $client->getResponse()->getContent());

        $deleteForm = $crawler->selectButton('Delete')->form();
        $client->submit($deleteForm);
        $this->assertTrue($client->getResponse()->isRedirect());

        $client->followRedirect();
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertContains('<!DOCTYPE html>', $client->getResponse()->getContent());
        $this->assertContains('SweetFood index', $client->getResponse()->getContent());
        $this->assertContains('no records found', $client->getResponse()->getContent());
    }
}
