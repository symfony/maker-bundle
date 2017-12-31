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

        $em->createQuery('DELETE FROM App\\Entity\\User f')
            ->execute();

        $food = new User();
        // set existing field
        $food->setFirstName('Mr. Chocolate');
        // set the new, generated field
        $food->setLastName('Cake');
        $em->persist($food);
        $em->flush();

        $actualFood = $em->getRepository(User::class)
            ->findAll();

        $this->assertcount(1, $actualFood);
    }
}
