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

use App\Dto\TaskData;
use Doctrine\Common\Annotations\AnnotationReader;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Validation;

class TaskDataTest extends KernelTestCase
{
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

    public function testValidation()
    {
        $validator = Validation::createValidatorBuilder()->enableAnnotationMapping()->getValidator();
        $task = new TaskData;
        $errors = $validator->validate($task);
        $this->assertEquals(1, count($errors));
        $task->setTask('foo');
        $errors = $validator->validate($task);
        $this->assertEquals(0, count($errors));
    }
}
