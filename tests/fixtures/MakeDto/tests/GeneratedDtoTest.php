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

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use App\Entity\Task;
use App\Form\Data\TaskData;
use Doctrine\Common\Annotations\AnnotationReader;
use Symfony\Component\Validator\Constraints\NotBlank;

class GeneratedDtoTest extends KernelTestCase
{
    public function testGeneratedDto()
    {
        $this->assertClassHasAttribute('task', TaskData::class);
        $this->assertClassHasAttribute('dueDate', TaskData::class);
        $this->assertClassNotHasAttribute('id', TaskData::class);

        $this->assertTrue(method_exists(TaskData::class, 'fill'));
        $this->assertTrue(method_exists(TaskData::class, 'extract'));

        $this->assertFalse(method_exists(TaskData::class, 'setTask'));
        $this->assertFalse(method_exists(TaskData::class, 'getTask'));

        $this->assertFalse(method_exists(TaskData::class, 'setDueDate'));
        $this->assertFalse(method_exists(TaskData::class, 'getDueDate'));
    }

    public function testHelpers()
    {
        $taskEntity = new Task();
        $taskEntity->setTask('Acme');
        $taskEntity->setDueDate(new \DateTime('2018-01-29 01:30'));

        $taskData = new TaskData($taskEntity);

        $this->assertEquals($taskEntity->getTask(), $taskData->task);
        $this->assertEquals($taskEntity->getDueDate(), $taskData->dueDate);

        $taskData->task = 'Foo';

        $taskEntity = new Task();
        $taskData->fill($taskEntity);

        $this->assertEquals($taskEntity->getTask(), $taskData->task);
        $this->assertEquals($taskEntity->getDueDate(), $taskData->dueDate);
    }

    public function testAnnotations()
    {
        $annotationReader = new AnnotationReader();
        $reflectionProperty = new \ReflectionProperty(TaskData::class, 'task');
        $propertyAnnotations = $annotationReader->getPropertyAnnotations($reflectionProperty);
        $this->assertCount(1, $propertyAnnotations);
        $this->assertContainsOnlyInstancesOf(NotBlank::class, $propertyAnnotations);

        $reflectionProperty = new \ReflectionProperty(TaskData::class, 'dueDate');
        $propertyAnnotations = $annotationReader->getPropertyAnnotations($reflectionProperty);
        $this->assertCount(0, $propertyAnnotations);
    }
}
