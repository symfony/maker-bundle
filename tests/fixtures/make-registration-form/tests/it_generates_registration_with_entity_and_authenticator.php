<?php

namespace App\Tests;

use App\Entity\User;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class RegistrationFormTest extends WebTestCase
{
    public function testRegistrationSuccessful()
    {
        $client = static::createClient();

        /** @var EntityManager $em */
        $em = self::$kernel->getContainer()
            ->get('doctrine')
            ->getManager();
        $em->createQuery('DELETE FROM App\\Entity\\User u')
            ->execute();

        $crawler = $client->request('GET', '/register');
        $form = $crawler->selectButton('Register')->form();
        $form['registration_form[email]'] = 'ryan@symfonycasts.com';
        $form['registration_form[plainPassword]'] = '1234yaaay';
        $form['registration_form[agreeTerms]'] = true;
        $client->submit($form);

        dump($client->getResponse());
        $this->assertSame(302, $client->getResponse()->getStatusCode());
        $client->followRedirect();
        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $this->assertSame('Page Success', $client->getResponse()->getContent());
    }

    public function testRegistrationValidationError()
    {
        $client = static::createClient();

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

        $crawler = $client->request('GET', '/register');
        $form = $crawler->selectButton('Register')->form();
        $form['registration_form[email]'] = 'ryan@symfonycasts.com';
        $form['registration_form[plainPassword]'] = 'foo';
        $client->submit($form);

        $this->assertSame(422, $client->getResponse()->getStatusCode());
        $this->assertStringContainsString(
            'There is already an account with this email',
            $client->getResponse()->getContent()
        );
        $this->assertStringContainsString(
            'Your password should be at least 6 characters',
            $client->getResponse()->getContent()
        );
        $this->assertStringContainsString(
            'You should agree to our terms.',
            $client->getResponse()->getContent()
        );
    }

    public function testVerifiedPropertyNotAddedToUserEntity(): void
    {
        self::assertFalse(method_exists(User::class, 'isVerified'));
        self::assertFalse(property_exists(User::class, 'verified'));
    }
}
