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
        $this->assertInstanceOf(UserAvatarPhoto::class, $user->getPhoto());
        $dm->persist($user);

        $dm->flush();
        $dm->refresh($user);

        $actualUser = $dm->getRepository(User::class)
            ->findAll();

        $this->assertcount(1, $actualUser);
        $actualUser[0]->getPhoto()->setFile('file');
        $dm->flush();
        $dm->clear();

        $actualUser = $dm->getRepository(User::class)
            ->findAll();
        $this->assertEquals('file', $actualUser[0]->getPhoto()->getFile());
    }
}
