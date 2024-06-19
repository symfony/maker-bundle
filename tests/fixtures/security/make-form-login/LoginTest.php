<?php

namespace App\Tests;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class LoginTest extends WebTestCase
{
    public function testLogin(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/login');

        self::assertResponseStatusCodeSame(200, 'Failed to get \'/login\'.');

        $em = static::getContainer()->get('doctrine')->getManager();

        $user = (new User())
            ->setEmail('jr@rushlow.dev')
            ->setPassword('wordpass')
        ;

        $em->persist($user);
        $em->flush();

        $form = $crawler->filter('form')->form();

        $form->setValues([
            '_username' => 'jr@rushlow.dev',
            '_password' => 'badPass',
        ]);


        $client->submit($form);

        self::assertResponseStatusCodeSame(302, 'Login with bad password is not status 302');
        $client->followRedirect();
        self::assertResponseStatusCodeSame(200, 'Problem with redirect after entering a bad password.');
        self::assertStringContainsString('Invalid credentials', $client->getResponse()->getContent());

        $form->setValues([
            '_username' => 'jr@rushlow.dev',
            '_password' => 'wordpass',
        ]);

        $client->submit($form);

        self::assertInstanceOf(User::class, $this->getToken()->getUser());

        $client->request('GET', '/logout');
        self::assertNull($this->getToken());
    }

    private function getToken(): ?TokenInterface
    {
        $tokenStorage = static::getContainer()->get('security.token_storage');
        $tokenStorage->disableUsageTracking();

        return $tokenStorage->getToken();
    }
}
