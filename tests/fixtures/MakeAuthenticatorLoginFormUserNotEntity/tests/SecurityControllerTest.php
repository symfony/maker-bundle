<?php

namespace App\Tests;

use App\Security\AppCustomAuthenticator;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SecurityControllerTest extends WebTestCase
{
    public function testCommand()
    {
        $authenticatorReflection = new \ReflectionClass(AppCustomAuthenticator::class);
        $constructorParameters = $authenticatorReflection->getConstructor()->getParameters();
        $this->assertSame('router', $constructorParameters[0]->getName());

        $client = self::createClient();
        $crawler = $client->request('GET', '/login');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $form = $crawler->filter('form')->form();
        $form->setValues(
            [
                'email'    => 'bar',
                'password' => 'foo',
            ]
        );
        $client->submit($form);

        $this->assertEquals(302, $client->getResponse()->getStatusCode());
        $client->followRedirect();
        $this->assertContains('Invalid credentials.', $client->getResponse()->getContent());
        $form->setValues(
            [
                'email'    => 'test@symfony.com',
                'password' => 'test@symfony.com',
            ]
        );
        $client->submit($form);

        $this->assertContains('TODO: provide a valid redirection', $client->getResponse()->getContent());
    }
}
