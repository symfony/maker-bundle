<?php

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Doctrine\ORM\EntityManager;
use App\Entity\User;

class GeneratedEntityTest extends KernelTestCase
{
    public function testGeneratedEntity()
    {
        // load up the database
        // create an entity, persist & query

        self::bootKernel();
        /** @var EntityManager $em */
        $em = self::$kernel->getContainer()
            ->get('doctrine')
            ->getManager();

        $em->createQuery('DELETE FROM App\\Entity\\User u')
            ->execute();

        $user = new User();
        $em->persist($user);
        $em->flush();
        $em->refresh($user);

        $actualUser = $em->getRepository(User::class)
            ->findAll();

        $this->assertcount(1, $actualUser);
    }
}
