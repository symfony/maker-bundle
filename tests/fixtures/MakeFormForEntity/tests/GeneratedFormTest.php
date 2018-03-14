<?php

namespace App\Tests;

use App\Entity\SourFood;
use App\Form\SourFoodType;
use Symfony\Component\Form\Test\TypeTestCase;

class GeneratedFormTest extends TypeTestCase
{
    public function testGeneratedForm()
    {
        $formData = [
            'title' => 'lemon'
        ];

        $form = $this->factory->create(SourFoodType::class);
        $form->submit($formData);

        $object = new SourFood();
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
