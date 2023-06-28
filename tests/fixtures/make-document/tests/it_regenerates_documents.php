<?php

namespace App\Tests;

use App\Document\User;
use App\Document\UserAvatar;
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
        $dm->createQueryBuilder(UserAvatar::class)
            ->remove()
            ->getQuery()
            ->execute();

        $user = new User();
        // check that the constructor was instantiated properly
        $this->assertInstanceOf(ArrayCollection::class, $user->getAvatars());
        // fields should now have setters
        $user->setFirstName('Ryan');
        $user->setCreatedAt(new \DateTime());
        $dm->persist($user);

        $photo = new UserAvatar();
        $photo->setUser($user);
        $dm->persist($photo);

        // set via the inverse side
        $photo2 = new UserAvatar();
        $user->addAvatar($photo2);
        $dm->persist($photo2);

        $dm->flush();
        $dm->refresh($user);

        $actualUser = $dm->getRepository(User::class)
            ->findAll();

        $this->assertcount(1, $actualUser);
        $this->assertCount(2, $actualUser[0]->getAvatars());
    }
}
