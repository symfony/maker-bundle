<?php

namespace App\Tests;

use App\Document\User;
use App\Document\UserProfile;
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
        $dm->createQueryBuilder(UserProfile::class)
            ->remove()
            ->getQuery()
            ->execute();

        $user = new User();
        // set existing field
        $user->setFirstName('Ryan');
        $dm->persist($user);

        $profile = new UserProfile();
        // set inverse side - will set owning
        $user->setUserProfile($profile);
        // purposely don't persist: cascade should be set
        // $em->persist($profile);

        $dm->flush();
        $dm->refresh($user);
        $dm->refresh($profile);

        $this->assertSame($profile, $user->getUserProfile());
        $this->assertSame($user, $profile->getUser());

        $dm->remove($user);
        // don't remove the profile, rely on cascade
        $dm->flush();

        $this->assertEmpty($dm->getRepository(UserProfile::class)->findAll());
    }
}
