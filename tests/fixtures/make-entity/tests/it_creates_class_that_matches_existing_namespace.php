<?php

namespace App\Tests;

use App\Entity\User\Category;
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

        $em->createQuery('DELETE FROM App\\Entity\\User\\Category c')
            ->execute();

        $category = new Category();
        $em->persist($category);
        $em->flush();
        $em->refresh($category);

        $actualCategories = $em->getRepository(Category::class)
            ->findAll();

        $this->assertcount(1, $actualCategories);
    }
}
