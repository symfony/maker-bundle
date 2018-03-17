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
        $this->assertInstanceOf(ArrayCollection::class, $user->getUserAvatarPhotos());
        // set existing field
        $user->setFirstName('Ryan');
        $em->persist($user);

        $photo = new UserAvatarPhoto();
        $photo->setUser($user);
        $em->persist($photo);

        // set via the inverse side
        $photo2 = new UserAvatarPhoto();
        $user->addUserAvatarPhoto($photo2);
        $em->persist($photo2);

        $em->flush();
        $em->refresh($user);

        $actualUser = $em->getRepository(User::class)
            ->findAll();

        $this->assertcount(1, $actualUser);
        $this->assertCount(2, $actualUser[0]->getUserAvatarPhotos());
    }
}
