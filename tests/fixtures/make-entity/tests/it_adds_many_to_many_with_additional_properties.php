<?php

namespace App\Tests;

use App\Entity\Group;
use App\Entity\User;
use App\Entity\UserGroup;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
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

        $this->cleanUp($em);

        $user = new User();
        $em->persist($user);

        $group = new Group();
        $em->persist($group);

        $userGroup = new UserGroup();
        $userGroup->setUser($user);
        $userGroup->setGroup($group);
        $userGroup->setRole('admin');
        $em->persist($userGroup);

        $em->flush();
        $em->clear();

        $actualUsers = $em->getRepository(User::class)->findAll();
        $this->assertCount(1, $actualUsers);
        $this->assertCount(1, $actualUsers[0]->getUserGroups());

        /** @var UserGroup $actualUserGroup */
        $actualUserGroup = $actualUsers[0]->getUserGroups()->first();
        $this->assertSame('admin', $actualUserGroup->getRole());
        $this->assertSame($actualUsers[0]->getId(), $actualUserGroup->getUser()->getId());

        $actualGroups = $em->getRepository(Group::class)->findAll();
        $this->assertCount(1, $actualGroups);
        $this->assertCount(1, $actualGroups[0]->getUserGroups());
        $this->assertSame($actualGroups[0]->getId(), $actualUserGroup->getGroup()->getId());

        // removing the association from the owning collection should delete it (orphanRemoval)
        $actualUsers[0]->getUserGroups()->removeElement($actualUserGroup);
        $em->flush();

        $this->assertCount(0, $em->getRepository(UserGroup::class)->findAll());
    }

    public function testUniqueConstraintIsEnforced()
    {
        self::bootKernel();
        /** @var EntityManager $em */
        $em = self::$kernel->getContainer()
            ->get('doctrine')
            ->getManager();

        $this->cleanUp($em);

        $user = new User();
        $em->persist($user);

        $group = new Group();
        $em->persist($group);

        $userGroup = new UserGroup();
        $userGroup->setUser($user);
        $userGroup->setGroup($group);
        $userGroup->setRole('admin');
        $em->persist($userGroup);
        $em->flush();

        $duplicateUserGroup = new UserGroup();
        $duplicateUserGroup->setUser($user);
        $duplicateUserGroup->setGroup($group);
        $duplicateUserGroup->setRole('member');
        $em->persist($duplicateUserGroup);

        $this->expectException(UniqueConstraintViolationException::class);
        $em->flush();
    }

    private function cleanUp(EntityManager $em): void
    {
        $em->createQuery('DELETE FROM App\\Entity\\UserGroup ug')->execute();
        $em->createQuery('DELETE FROM App\\Entity\\User u')->execute();
        $em->createQuery('DELETE FROM App\\Entity\\Group g')->execute();
    }
}
