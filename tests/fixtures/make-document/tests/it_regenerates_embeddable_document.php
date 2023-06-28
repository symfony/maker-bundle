<?php

namespace App\Tests;

use App\Document\Food;
use App\Document\Recipe;
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

        $dm->createQueryBuilder(Food::class)
            ->remove()
            ->getQuery()
            ->execute();

        $food = new Food();
        // check that the constructor was instantiated properly
        $this->assertInstanceOf(Recipe::class, $food->getRecipe());
        // fields should now have setters
        $food->setTitle('Borscht');

        $recipe = new Recipe();
        $recipe->setIngredients('ingridients');
        $recipe->setSteps('steps');
        $food->setRecipe($recipe);

        $dm->persist($food);

        $dm->flush();
        $dm->refresh($food);

        /** @var Food[] $actualFood */
        $actualFood = $dm->getRepository(Food::class)
            ->findAll();

        $this->assertcount(1, $actualFood);

        /** @var Recipe $actualRecipe */
        $actualRecipe = $actualFood[0]->getRecipe();

        $this->assertInstanceOf(Recipe::class, $actualRecipe);
        $this->assertEquals('ingridients', $actualRecipe->getIngredients());
        $this->assertEquals('steps', $actualRecipe->getSteps());
    }
}
