<?php

namespace App\Tests;

use Doctrine\ORM\EntityManager;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoder;

class RegistrationFormTest extends WebTestCase
{
    public function testGeneratedEntity()
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
        $this->assertSame('Homepage Success', $client->getResponse()->getContent());
    }
}
