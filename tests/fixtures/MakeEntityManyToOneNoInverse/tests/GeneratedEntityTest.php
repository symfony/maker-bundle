<?php

namespace App\Tests;

use App\Entity\User;
use App\Entity\UserAvatarPhoto;
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
        $em->createQuery('DELETE FROM App\\Entity\\UserAvatarPhoto u')->execute();

        $user = new User();
        $em->persist($user);

        $photo = new UserAvatarPhoto();
        $photo->setUser($user);
        $em->persist($photo);

        $em->flush();
        $em->refresh($photo);

        $this->assertSame($photo->getUser(), $user);
    }
}
