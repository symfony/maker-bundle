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

        self::assertEmailCount(1);

        /** @var EntityManager $em */
        $em = self::$kernel->getContainer()
            ->get('doctrine')
            ->getManager()
        ;

        $query = $em->createQuery('SELECT u FROM App\\Entity\\User u WHERE u.email = \'jr@rushlow.dev\'');

        self::assertFalse(($query->getSingleResult())->isVerified());

        $messageBody = self::getMailerMessage()->getHtmlBody();

        preg_match('/(http.*)(")/', $messageBody, $signedUrl);

        $parsed = parse_url($signedUrl[1]);

        $decodedQueryString = str_replace('amp;', '', $parsed['query']);

        $client->request('GET', sprintf('%s://%s%s?%s', $parsed['scheme'], $parsed['host'], $parsed['path'], $decodedQueryString));

        self::assertTrue(($query->getSingleResult())->isVerified());
    }
}
