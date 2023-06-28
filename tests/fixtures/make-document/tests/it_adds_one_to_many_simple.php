<?php

namespace App\Tests;

use App\Document\User;
use App\Document\UserAvatarPhoto;
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
        $dm->createQueryBuilder(UserAvatarPhoto::class)
            ->remove()
            ->getQuery()
            ->execute();

        $user = new User();
        // check that the constructor was instantiated properly
        $this->assertInstanceOf(ArrayCollection::class, $user->getPhotos());
        $dm->persist($user);

        $photo = new UserAvatarPhoto();
        $photo->setUser($user);
        $dm->persist($photo);

        // set via the inverse side
        $photo2 = new UserAvatarPhoto();
        $user->addPhoto($photo2);
        $dm->persist($photo2);

        $photo3 = new UserAvatarPhoto();
        $user->addPhoto($photo3);
        $dm->persist($photo3);

        $dm->flush();
        $dm->refresh($user);

        $actualUser = $dm->getRepository(User::class)
            ->findAll();

        $this->assertcount(1, $actualUser);
        $this->assertCount(3, $actualUser[0]->getPhotos());

        // remove some photos!
        $user->removePhoto($photo3);
        $dm->remove($photo3);
        $dm->flush();

        $dm->clear();

        $actualUser = $dm->getRepository(User::class)
            ->findAll();
        $this->assertCount(2, $actualUser[0]->getPhotos());

        $allUserPhotos = $dm->getRepository(UserAvatarPhoto::class)
            ->findAll();
        // thanks to orphanRemoval, photo3 should be fully deleted
        $this->assertCount(2, $allUserPhotos);
    }
}
