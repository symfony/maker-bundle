<?php

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class GeneratedCrudControllerTest extends WebTestCase
{
    public function testIndexAction()
    {
        $client = self::createClient();
        $crawler = $client->request('GET', '/sweet/food');
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertStringContainsString('<!DOCTYPE html>', $client->getResponse()->getContent());
        $this->assertStringContainsString('SweetFood index', $client->getResponse()->getContent());

        $newLink = $crawler->filter('a:contains("Create new")')->eq(0)->link();

        $crawler = $client->click($newLink);
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertStringContainsString('<!DOCTYPE html>', $client->getResponse()->getContent());
        $this->assertStringContainsString('New SweetFood', $client->getResponse()->getContent());

        $newForm = $crawler->selectButton('Save')->form();
        $client->submit($newForm, ['sweet_food_form[title]' => 'Candy']);
        $this->assertTrue($client->getResponse()->isRedirect());

        $crawler = $client->followRedirect();
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertStringContainsString('<!DOCTYPE html>', $client->getResponse()->getContent());
        $this->assertStringContainsString('SweetFood index', $client->getResponse()->getContent());
        $this->assertStringContainsString('<td>Candy</td>', $client->getResponse()->getContent());

        $editLink = $crawler->filter('a:contains("edit")')->eq(0)->link();
        $crawler = $client->click($editLink);
        $this->assertStringContainsString('<!DOCTYPE html>', $client->getResponse()->getContent());
        $this->assertStringContainsString('Edit SweetFood', $client->getResponse()->getContent());
        $this->assertGreaterThan(0, $crawler->filter('input[type=text]')->count());

        $editForm = $crawler->selectButton('Update')->form();
        $client->submit($editForm, ['sweet_food_form[title]' => 'Candy edited']);
        $this->assertTrue($client->getResponse()->isRedirect());

        $crawler = $client->followRedirect();
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertStringContainsString('<!DOCTYPE html>', $client->getResponse()->getContent());
        $this->assertStringContainsString('SweetFood index', $client->getResponse()->getContent());
        $this->assertStringContainsString('Candy edited', $client->getResponse()->getContent());

        $showLink = $crawler->filter('a:contains("show")')->eq(0)->link();

        $crawler = $client->click($showLink);
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertStringContainsString('<!DOCTYPE html>', $client->getResponse()->getContent());
        $this->assertStringContainsString('SweetFood', $client->getResponse()->getContent());
        $this->assertStringContainsString('Candy edited', $client->getResponse()->getContent());

        $deleteForm = $crawler->selectButton('Delete')->form();
        $client->submit($deleteForm);
        $this->assertTrue($client->getResponse()->isRedirect());

        $client->followRedirect();
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertStringContainsString('<!DOCTYPE html>', $client->getResponse()->getContent());
        $this->assertStringContainsString('SweetFood index', $client->getResponse()->getContent());
        $this->assertStringContainsString('no records found', $client->getResponse()->getContent());
    }
}
