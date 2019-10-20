<?php

namespace App\Tests;

use App\Entity\User;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoder;

class GeneratedEntityTest extends WebTestCase
{
    public function testGeneratedEntity()
    {
        self::bootKernel();
        /** @var EntityManager $em */
        $em = self::$kernel->getContainer()
            ->get('doctrine')
            ->getManager();
        /** @var UserPasswordEncoder $encoder */
        $encoder = self::$kernel->getContainer()
            ->get('security.password_encoder');

        $em->createQuery('DELETE FROM App\\Entity\\User u')
            ->execute();

        $user = new User();
        $user->setEmail('foo@example.com');
        $user->setPassword($encoder->encodePassword($user, 'pa$$'));

        $em->persist($user);
        $em->flush();

        // login then access a protected page
        $client = static::createClient();
        $client->request('GET', '/login?email=foo@example.com');

        $this->assertSame(302, $client->getResponse()->getStatusCode());
        $client->followRedirect();
        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $this->assertSame('Homepage Success', $client->getResponse()->getContent());
    }
}
