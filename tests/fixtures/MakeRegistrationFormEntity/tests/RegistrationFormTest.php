<?php

namespace App\Tests;

use Doctrine\ORM\EntityManager;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoder;

class RegistrationFormTest extends WebTestCase
{
    public function testRegistrationSuccessful()
    {
        self::bootKernel();
        /** @var EntityManager $em */
        $em = self::$kernel->getContainer()
            ->get('doctrine')
            ->getManager();
        $em->createQuery('DELETE FROM App\\Entity\\User u')
            ->execute();

        $client = static::createClient();
        $crawler = $client->request('GET', '/register');
        $form = $crawler->selectButton('Register')->form();
        $form['registration_form[email]'] = 'ryan@symfonycasts.com';
        $form['registration_form[plainPassword]'] = '1234yaaay';
        $client->submit($form);

        $this->assertSame(302, $client->getResponse()->getStatusCode());
        $client->followRedirect();
        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $this->assertSame('Page Success', $client->getResponse()->getContent());
    }

    public function testRegistrationValidationError()
    {
        self::bootKernel();
        /** @var EntityManager $em */
        $em = self::$kernel->getContainer()
            ->get('doctrine')
            ->getManager();
        $em->createQuery('DELETE FROM App\\Entity\\User u')
            ->execute();
        $user = new User();
        $user->setEmail('ryan@symfonycasts.com');
        $user->setPassword('abc-fake-encoded');
        $em->persist($user);
        $em->flush();

        $client = static::createClient();
        $crawler = $client->request('GET', '/register');
        $form = $crawler->selectButton('Register')->form();
        $form['registration_form[email]'] = 'ryan@symfonycasts.com';
        $form['registration_form[plainPassword]'] = 'foo';
        $client->submit($form);

        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $this->assertContains(
            'There is already an account with this email',
            $client->getResponse()->getContent()
        );
        $this->assertContains(
            'Your password should be at least 6 characters',
            $client->getResponse()->getContent()
        );
    }
}
