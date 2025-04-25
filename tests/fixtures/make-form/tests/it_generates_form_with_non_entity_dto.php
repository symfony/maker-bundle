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

use App\Form\Data\TaskData;
use App\Form\TaskForm;
use Symfony\Component\Form\Test\TypeTestCase;

class GeneratedFormTest extends TypeTestCase
{
    public function testGeneratedForm()
    {
        $dateTimeObject = new \DateTime();

        $formData = [
            'task' => 'Acme',
            'dueDate' => $dateTimeObject,
        ];

        $objectToCompare = new TaskData();

        $form = $this->factory->create(TaskForm::class, $objectToCompare);
        $form->submit($formData);

        $object = new TaskData();
        $object->task = 'Acme';
        $object->dueDate = $dateTimeObject;

        $this->assertTrue($form->isSynchronized());
        $this->assertEquals($object, $objectToCompare);
        $this->assertEquals($object, $form->getData());

        $view = $form->createView();
        $children = $view->children;

        foreach (array_keys($formData) as $key) {
            $this->assertArrayHasKey($key, $children);
        }
    }
}
