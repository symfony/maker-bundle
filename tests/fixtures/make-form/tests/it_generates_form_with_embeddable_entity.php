<?php

namespace App\Tests;

use App\Entity\Food;
use App\Form\FoodType;
use Symfony\Component\Form\Test\TypeTestCase;

class GeneratedFormTest extends TypeTestCase
{
    public function testGeneratedForm()
    {
        $formData = [
            'title' => 'lemon',
        ];

        $form = $this->factory->create(FoodType::class);
        $form->submit($formData);

        $object = new Food();
        $object->setTitle('lemon');

        $this->assertTrue($form->isSynchronized());
        $this->assertEquals($object, $form->getData());

        $view = $form->createView();
        $children = $view->children;

        foreach (array_keys($formData) as $key) {
            $this->assertArrayHasKey($key, $children);
        }
    }
}
