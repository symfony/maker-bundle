<?php

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Doctrine\ORM\EntityManager;
use App\Entity\Product\Category;

class GeneratedEntityTest extends KernelTestCase
{
    public function testGeneratedEntity()
    {
        self::bootKernel();
        /** @var EntityManager $em */
        $em = self::$kernel->getContainer()
            ->get('doctrine')
            ->getManager();

        $em->createQuery('DELETE FROM App\\Entity\\Product\\Category c')
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
