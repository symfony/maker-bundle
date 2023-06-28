<?php

namespace App\Tests;

use App\Document\User;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class GeneratedDocumentTest extends KernelTestCase
{
    public function testGeneratedDocument()
    {
        self::bootKernel();
        /** @var \Doctrine\ODM\MongoDB\DocumentManager $dm */
        $dm = self::$kernel->getContainer()
            ->get('doctrine_mongodb')
            ->getManager();

        $dm->createQueryBuilder(User::class)
            ->remove()
            ->getQuery()
            ->execute();

        $user = new User();
        // check that the constructor was instantiated properly
        $this->assertInstanceOf(ArrayCollection::class, $user->getDependants());
        // set existing field
        $user->setFirstName('Ryan');
        $dm->persist($user);

        $ward = new User();
        $ward->setFirstName('Tim');
        $ward->setGuardian($user);
        $dm->persist($ward);

        // set via the inverse side
        $ward2 = new User();
        $ward2->setFirstName('Fabien');
        $user->addDependant($ward2);
        $dm->persist($ward2);

        $dm->flush();
        $dm->refresh($user);

        $actualUser = $dm->getRepository(User::class)
            ->findAll();

        $this->assertCount(3, $actualUser);
        $this->assertCount(2, $actualUser[0]->getDependants());
    }
}
