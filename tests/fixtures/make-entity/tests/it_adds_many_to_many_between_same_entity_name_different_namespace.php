<?php

namespace App\Tests;

use App\Entity\Friend\User as FriendUser;
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
        $em->createQuery('DELETE FROM App\\Entity\\Friend\\User u')->execute();

        $user = new User();
        // check that the constructor was instantiated properly
        $this->assertInstanceOf(ArrayCollection::class, $user->getFriends());
        $em->persist($user);

        $friend = new FriendUser();
        $user->addFriend($friend);
        $em->persist($friend);

        // set via the inverse side
        $friend2 = new FriendUser();
        $friend2->addUser($user);
        $em->persist($friend2);

        $em->flush();
        $em->refresh($user);
        $em->refresh($friend);
        $em->refresh($friend2);

        $this->assertCount(2, $user->getFriends());
        $this->assertCount(1, $friend->getUsers());

        // remove from the owning side, then check the inverse side
        $user->removeFriend($friend);
        $em->flush();
        $em->refresh($friend);
        $this->assertCount(1, $user->getFriends());
        $this->assertEmpty($friend->getUsers());

        $this->assertCount(1, $em->getRepository(User::class)->findAll());
        $this->assertCount(2, $em->getRepository(FriendUser::class)->findAll());
    }
}
