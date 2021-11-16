<?php

namespace App\Tests;

use App\Entity\User;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoder;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

class GeneratedUserTest extends WebTestCase
{
    public function testGeneratedEntity()
    {
        $client = static::createClient();

        /** @var EntityManager $em */
        $em = self::$kernel->getContainer()
            ->get('doctrine')
            ->getManager();
        /** @var UserPasswordHasherInterface $hasher */
        $hasher = self::$kernel->getContainer()
            ->get('test_password_hasher');

        $em->createQuery('DELETE FROM App\\Entity\\User u')
            ->execute();

        $user = new User();
        $user->setEmail('foo@example.com');
        $user->setPassword($hasher->hashPassword($user, 'pa$$'));

        $reflectedUser = new \ReflectionClass(User::class);
        self::assertTrue($reflectedUser->implementsInterface(PasswordAuthenticatedUserInterface::class));
        self::assertTrue($reflectedUser->hasMethod('getPassword'));

        $em->persist($user);
        $em->flush();

        // login then access a protected page
        $client->request('GET', '/login?email=foo@example.com');

        $this->assertSame(302, $client->getResponse()->getStatusCode());
        $client->followRedirect();
        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $this->assertSame('Homepage Success. Hello: foo@example.com', $client->getResponse()->getContent());
    }
}
