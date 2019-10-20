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

        // assert authenticator is *not* injected
        $this->assertCount(3, $constructorParameters);

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

        $this->assertStringContainsString('TODO: check the credentials', $client->getResponse()->getContent());
    }
}
