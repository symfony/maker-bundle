<?php

namespace App\Tests;

use App\Document\User\Category;

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

        $dm->createQueryBuilder(Category::class)
            ->remove()
            ->getQuery()
            ->execute();

        $category = new Category();
        $dm->persist($category);
        $dm->flush();
        $dm->refresh($category);

        $actualCategories = $dm->getRepository(Category::class)
            ->findAll();

        $this->assertcount(1, $actualCategories);
    }
}
