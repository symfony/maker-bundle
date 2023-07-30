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

        $user = new User();
        // check that the constructor was instantiated properly
        $this->assertInstanceOf(ArrayCollection::class, $user->getPhotos());
        $dm->persist($user);

        $photo = new UserAvatarPhoto();
        $photo->setFile('file1');
        $user->addPhoto($photo);

        // set via the inverse side
        $photo2 = new UserAvatarPhoto();
        $photo->setFile('file2');
        $user->addPhoto($photo2);

        $photo3 = new UserAvatarPhoto();
        $photo->setFile('file3');
        $user->addPhoto($photo3);

        $dm->flush();
        $dm->refresh($user);

        $actualUser = $dm->getRepository(User::class)
            ->findAll();

        $this->assertcount(1, $actualUser);
        $this->assertCount(3, $actualUser[0]->getPhotos());

        // remove some photos!
        $removePhoto = $actualUser[0]->getPhotos()->first();
        $user->removePhoto($removePhoto);
        $dm->flush();

        $dm->clear();

        $actualUser = $dm->getRepository(User::class)
            ->findAll();
        $this->assertCount(2, $actualUser[0]->getPhotos());
    }
}
