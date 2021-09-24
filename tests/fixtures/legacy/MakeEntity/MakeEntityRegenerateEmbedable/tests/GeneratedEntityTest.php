<?php

namespace App\Tests;

use App\Entity\Food;
use App\Entity\Recipe;
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

        $em->createQuery('DELETE FROM App\\Entity\\Food f')->execute();

        $food = new Food();
        // check that the constructor was instantiated properly
        $this->assertInstanceOf(Recipe::class, $food->getRecipe());
        // fields should now have setters
        $food->setTitle('Borscht');

        $recipe = new Recipe();
        $recipe->setIngredients('ingridients');
        $recipe->setSteps('steps');
        $food->setRecipe($recipe);

        $em->persist($food);

        $em->flush();
        $em->refresh($food);

        /** @var Food[] $actualFood */
        $actualFood = $em->getRepository(Food::class)
            ->findAll();

        $this->assertcount(1, $actualFood);

        /** @var Recipe $actualRecipe */
        $actualRecipe = $actualFood[0]->getRecipe();

        $this->assertInstanceOf(Recipe::class, $actualRecipe);
        $this->assertEquals('ingridients', $actualRecipe->getIngredients());
        $this->assertEquals('steps', $actualRecipe->getSteps());
    }
}
