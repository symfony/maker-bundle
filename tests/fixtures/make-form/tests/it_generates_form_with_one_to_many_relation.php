<?php

namespace App\Tests;

use App\Entity\Author;
use App\Entity\Book;
use App\Form\AuthorType;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\TypeTestCase;

class GeneratedFormTest extends TypeTestCase
{
    public function testGeneratedForm()
    {
        $formData = [
            'name' => 'foo',
        ];

        $form = $this->factory->create(AuthorType::class);
        $form->submit($formData);

        $object = new Author();
        $object->setName('foo');
        $this->assertTrue($form->isSynchronized());
        $this->assertEquals($object, $form->getData());

        $view = $form->createView();
        $children = $view->children;

        foreach (array_keys($formData) as $key) {
            $this->assertArrayHasKey($key, $children);
        }
        $this->assertArrayNotHasKey('books', $children);
    }
}
