<?php

namespace App\Tests;

use App\Document\User;
use App\Document\UserAvatarPhoto;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class GeneratedDocumentTest extends KernelTestCase
{
    public function testGeneratedDocument()
    {
        self::bootKernel();
        /** @var DocumentManager $dm */
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
        $dm->persist($user);

        $photo = new UserAvatarPhoto();
        $photo->setUser($user);
        $dm->persist($photo);

        $dm->flush();
        $dm->refresh($photo);

        $this->assertSame($photo->getUser(), $user);
    }
}
