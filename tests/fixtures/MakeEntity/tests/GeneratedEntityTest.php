<?php

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Doctrine\ORM\EntityManager;
use App\Entity\TastyFood;

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

        $em->createQuery('DELETE FROM App\\Entity\\TastyFood f')
            ->execute();

        $food = new TastyFood();
        $em->persist($food);
        $em->flush();

        $actualFood = $em->getRepository(TastyFood::class)
            ->findAll();

        $this->assertcount(1, $actualFood);
    }
}
