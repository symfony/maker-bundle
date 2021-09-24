<?php

namespace App\Tests;

use App\Entity\User;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class GeneratedEntityTest extends KernelTestCase
{
    public function testGeneratedEntity()
    {
        self::bootKernel();
        /** @var EntityManager $em */
        $em = self::$kernel->getContainer()
            ->get('doctrine')
            ->getManager();

        $em->createQuery('DELETE FROM App\\Entity\\User u')->execute();

        $user = new User();
        // check that the constructor was instantiated properly
        $this->assertInstanceOf(ArrayCollection::class, $user->getDependants());
        // set existing field
        $user->setFirstName('Ryan');
        $em->persist($user);

        $ward = new User();
        $ward->setFirstName('Tim');
        $ward->setGuardian($user);
        $em->persist($ward);

        // set via the inverse side
        $ward2 = new User();
        $ward2->setFirstName('Fabien');
        $user->addDependant($ward2);
        $em->persist($ward2);

        $em->flush();
        $em->refresh($user);

        $actualUser = $em->getRepository(User::class)
            ->findAll();

        $this->assertCount(3, $actualUser);
        $this->assertCount(2, $actualUser[0]->getDependants());
    }
}
