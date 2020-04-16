<?php

/*
 * This file is part of the Symfony MakerBundle package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests;

use App\Entity\Product\Category;
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
