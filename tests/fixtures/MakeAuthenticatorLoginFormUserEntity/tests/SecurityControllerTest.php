<?php

namespace App\Tests;

use App\Entity\User;
use App\Security\AppCustomAuthenticator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SecurityControllerTest extends WebTestCase
{
    public function testCommand()
    {
        $authenticatorReflection = new \ReflectionClass(AppCustomAuthenticator::class);
        $constructorParameters = $authenticatorReflection->getConstructor()->getParameters();
        $this->assertSame('entityManager', $constructorParameters[0]->getName());

        // assert authenticator is injected
        $this->assertCount(4, $constructorParameters);

        $client = self::createClient();
        $crawler = $client->request('GET', '/login');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        /** @var EntityManagerInterface $em */
        $em = self::$kernel->getContainer()
            ->get('doctrine')
            ->getManager();

        $user = (new User())->setUserEmail('test@symfony.com')
            ->setPassword('password');
        $em->persist($user);
        $em->flush();

        $form = $crawler->filter('form')->form();
        $form->setValues(
            [
                'userEmail' => 'test@symfony.com',
                'password' => 'foo',
            ]
        );
        $crawler = $client->submit($form);

        if (500 === $client->getResponse()->getStatusCode()) {
            $this->assertEquals('', $crawler->filter('h1.exception-message')->text());
        }

        $this->assertEquals(302, $client->getResponse()->getStatusCode());
        $client->followRedirect();
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertStringContainsString('Invalid credentials.', $client->getResponse()->getContent());

        $form->setValues(
            [
                'userEmail' => 'test@symfony.com',
                'password' => 'password',
            ]
        );
        $client->submit($form);

        $this->assertStringContainsString('TODO: provide a valid redirect', $client->getResponse()->getContent());
        $this->assertNotNull($token = $client->getContainer()->get('security.token_storage')->getToken());
        $this->assertInstanceOf(User::class, $token->getUser());
    }
}
