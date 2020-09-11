<?php

namespace App\Tests;

use App\Entity\User;
use App\Entity\UserProfile;
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
        $em->createQuery('DELETE FROM App\\Entity\\UserProfile u')->execute();

        $user = new User();
        // set existing field
        $user->setFirstName('Ryan');
        $em->persist($user);

        $profile = new UserProfile();
        // set inverse side - will set owning
        $user->setUserProfile($profile);
        // purposely don't persist: cascade should be set
        // $em->persist($profile);

        $em->flush();
        $em->refresh($user);
        $em->refresh($profile);

        $this->assertSame($profile, $user->getUserProfile());
        $this->assertSame($user, $profile->getUser());

        $em->remove($user);
        // don't remove the profile, rely on cascade
        $em->flush();

        $this->assertEmpty($em->getRepository(UserProfile::class)->findAll());
    }
}
