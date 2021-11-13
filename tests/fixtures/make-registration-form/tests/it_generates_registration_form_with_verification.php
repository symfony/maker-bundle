<?php

namespace Symfony\Bundle\MakerBundle\Tests\fixtures\MakeRegistrationFormVerifyEmailFunctionalTest\tests;

use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class RegistrationFormTest extends WebTestCase
{
    public function testRegistrationSuccessful()
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/register');

        $form = $crawler->selectButton('Register')->form();
        $form['registration_form[email]'] = 'jr@rushlow.dev';
        $form['registration_form[plainPassword]'] = 'makeDockerComingSoon!';
        $form['registration_form[agreeTerms]'] = true;

        $client->submit($form);

        $messages = $this->getMailerMessages();
        self::assertCount(1, $messages);

        /** @var EntityManager $em */
        $em = self::$kernel->getContainer()
            ->get('doctrine')
            ->getManager()
        ;

        $query = $em->createQuery('SELECT u FROM App\\Entity\\User u WHERE u.email = \'jr@rushlow.dev\'');

        self::assertFalse(($query->getSingleResult())->isVerified());

        $context = $messages[0]->getContext();

        $client->request('GET', $context['signedUrl']);

        self::assertTrue(($query->getSingleResult())->isVerified());
    }
}
