<?php

namespace App\Tests;

use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Doctrine\ORM\EntityManager;
use App\Entity\User;
use App\Entity\Course;

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
        $em->createQuery('DELETE FROM App\\Entity\\Course u')->execute();

        $user = new User();
        // check that the constructor was instantiated properly
        $this->assertInstanceOf(ArrayCollection::class, $user->getCourses());
        $em->persist($user);

        $course = new Course();
        $course->addStudent($user);
        $em->persist($course);

        // set via the inverse side
        $course2 = new Course();
        $user->addCourse($course2);
        $em->persist($course2);

        $course3 = new Course();
        $user->addCourse($course3);
        $em->persist($course3);

        $em->flush();
        $em->refresh($user);

        $actualUser = $em->getRepository(User::class)
            ->findAll();

        $this->assertcount(1, $actualUser);
        $this->assertCount(3, $actualUser[0]->getCourses());

        // remove some!
        $user->removeCourse($course3);
        $course2->removeStudent($user);
        $em->flush();
        $em->refresh($user);
        $em->refresh($course2);
        // we removed course3, and course2 removed us!
        $this->assertCount(1, $user->getCourses());
        $this->assertEmpty($course2->getStudents());
    }
}
