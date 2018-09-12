<?php

namespace App\Tests;

use App\Controller\SecurityController;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SecurityControllerTest extends WebTestCase
{
    public function testCommand()
    {
        $this->assertTrue(method_exists(SecurityController::class, 'login'));
        $this->assertTrue(method_exists(SecurityController::class, 'logout'));

        $client = self::createClient();
        $crawler = $client->request('GET', '/login');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $form = $crawler->filter('form')->form();
        $client->submit($form);

        $this->assertEquals(302, $client->getResponse()->getStatusCode());
        $client->followRedirect();
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertContains('Username could not be found.', $client->getResponse()->getContent());
    }
}
