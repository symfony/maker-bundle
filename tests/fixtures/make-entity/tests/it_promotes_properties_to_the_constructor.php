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

        $em->createQuery('DELETE FROM App\\Entity\\UserAvatarPhoto u')->execute();
        $em->createQuery('DELETE FROM App\\Entity\\User u')->execute();

        $user = new User();
        $em->persist($user);
        $reviewer = new User();
        $em->persist($reviewer);

        $takenAt = new \DateTimeImmutable('2024-01-01 12:00:00');
        $photo = new UserAvatarPhoto('On the beach', $takenAt, $user);

        // the constructor sets the required properties, the optional ones have their default value
        $this->assertSame('On the beach', $photo->getCaption());
        $this->assertSame($takenAt, $photo->getTakenAt());
        $this->assertSame($user, $photo->getUser());
        $this->assertNull($photo->getDescription());
        $this->assertSame([], $photo->getTags());
        $this->assertNull($photo->getReviewer());

        // a nullable relation is an optional argument of the constructor
        $reviewedPhoto = new UserAvatarPhoto('In the mountains', $takenAt, $user, reviewer: $reviewer);
        $this->assertSame($reviewer, $reviewedPhoto->getReviewer());

        $user->addPhoto($photo);
        $user->addPhoto($reviewedPhoto);
        $reviewer->addReviewedPhoto($reviewedPhoto);
        $em->persist($photo);
        $em->persist($reviewedPhoto);
        $em->flush();
        $em->clear();

        // Doctrine does not need the constructor to load the entity
        $photo = $em->getRepository(UserAvatarPhoto::class)->findOneBy(['caption' => 'On the beach']);
        $this->assertNotNull($photo);
        $this->assertEquals($takenAt, $photo->getTakenAt());
        $this->assertNull($photo->getReviewer());
        $this->assertCount(2, $photo->getUser()->getPhotos());

        // unlike the user, the reviewer can be unset from the other side
        $reviewedPhoto = $em->getRepository(UserAvatarPhoto::class)->findOneBy(['caption' => 'In the mountains']);
        $this->assertNotNull($reviewedPhoto->getReviewer());
        $reviewedPhoto->getReviewer()->removeReviewedPhoto($reviewedPhoto);
        $this->assertNull($reviewedPhoto->getReviewer());
        $em->flush();
        $em->clear();

        $reviewedPhoto = $em->getRepository(UserAvatarPhoto::class)->findOneBy(['caption' => 'In the mountains']);
        $this->assertNull($reviewedPhoto->getReviewer());

        // the photo keeps its user, which is fine: orphanRemoval deletes it
        $photo = $em->getRepository(UserAvatarPhoto::class)->findOneBy(['caption' => 'On the beach']);
        $photo->getUser()->removePhoto($photo);
        $em->flush();

        $this->assertCount(1, $em->getRepository(UserAvatarPhoto::class)->findAll());
    }
}
