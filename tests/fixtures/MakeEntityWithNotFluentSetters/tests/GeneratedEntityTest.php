<?php

namespace App\Tests;

use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Doctrine\ORM\EntityManager;
use App\Entity\User;

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
        $user->setName('Piotr');
        $user->setEmail('piotr@isedo.pl');

        $em->persist($user);

        $em->flush();
        $em->refresh($user);

        $actualUser = $em->getRepository(User::class)
            ->findAll();

        $this->assertcount(1, $actualUser);
    }

    public function testExceptionOnChainingSetters()
    {
        $this->expectException(\Throwable::class);
        $this->expectExceptionMessage('Call to a member function setEmail() on null');

        self::bootKernel();
        /** @var EntityManager $em */
        $em = self::$kernel->getContainer()
            ->get('doctrine')
            ->getManager();

        $em->createQuery('DELETE FROM App\\Entity\\User u')->execute();

        $user = new User();
        $user
            ->setName('Piotr')
            ->setEmail('piotr@isedo.pl');
    }
}
