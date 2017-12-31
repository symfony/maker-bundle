<?php

namespace App\Tests;

use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Doctrine\ORM\EntityManager;
use App\Entity\User;
use App\Entity\UserAvatarPhoto;

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
        $em->createQuery('DELETE FROM App\\Entity\\UserAvatarPhoto u')->execute();

        $user = new User();
        // check that the constructor was instantiated properly
        $this->assertInstanceOf(ArrayCollection::class, $user->getPhotos());
        $em->persist($user);

        $photo = new UserAvatarPhoto();
        $photo->setUser($user);
        $em->persist($photo);

        // set via the inverse side
        $photo2 = new UserAvatarPhoto();
        $user->addPhoto($photo2);
        $em->persist($photo2);

        $photo3 = new UserAvatarPhoto();
        $user->addPhoto($photo3);
        $em->persist($photo3);

        $em->flush();
        $em->refresh($user);

        $actualUser = $em->getRepository(User::class)
            ->findAll();

        $this->assertcount(1, $actualUser);
        $this->assertCount(3, $actualUser[0]->getPhotos());

        // remove some photos!
        $user->removePhoto($photo3);
        $em->flush();
        $em->refresh($user);

        $actualUser = $em->getRepository(User::class)
            ->findAll();
        $this->assertCount(2, $actualUser[0]->getPhotos());
        $allUserPhotos = $em->getRepository(UserAvatarPhoto::class)
            ->findAll();
        // thanks to orphanRemoval, photo3 should be fully deleted
        $this->assertCount(2, $allUserPhotos);
    }
}
