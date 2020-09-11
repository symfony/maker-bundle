<?php

namespace App\Tests;

use App\Controller\SecurityController;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SecurityControllerTest extends WebTestCase
{
    public function testCommand()
    {
        $this->assertTrue(method_exists(SecurityController::class, 'login'));

        $client = self::createClient();
        $crawler = $client->request('GET', '/login');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        /** @var EntityManagerInterface $em */
        $em = self::$kernel->getContainer()
            ->get('doctrine')
            ->getManager();

        $user = (new User())->setEmail('test@symfony.com')
            ->setPassword('password');
        $em->persist($user);
        $em->flush();

        $form = $crawler->filter('form')->form();
        $form->setValues(
            [
                'email' => 'test@symfony.com',
                'password' => 'foo',
            ]
        );
        $client->submit($form);

        $this->assertEquals(302, $client->getResponse()->getStatusCode());
        $client->followRedirect();
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertStringContainsString('Invalid credentials.', $client->getResponse()->getContent());

        $form->setValues(
            [
                'email' => 'test@symfony.com',
                'password' => 'password',
            ]
        );
        $client->submit($form);

        $this->assertStringContainsString('TODO: provide a valid redirect', $client->getResponse()->getContent());
    }
}
