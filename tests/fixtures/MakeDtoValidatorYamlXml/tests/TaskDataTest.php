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
use App\Entity\Task;
use Doctrine\Common\Annotations\AnnotationReader;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Constraints\Type;
use Symfony\Component\Validator\Validation;

class TaskDataTest extends KernelTestCase
{
    public function testGeneratedDto()
    {
        // simply test if DTO validates with only the field with annotation being valid.
        $taskData = new TaskData();

        // valid
        $taskData->task = 'foobar';

        // invalid, but only with constraint from validation.yaml
        $taskData->dueDate = 'foo';

        // create validator
        $validator = Validation::createValidatorBuilder()
            ->enableAnnotationMapping()
            ->getValidator();

        $this->assertEmpty($validator->validate($taskData));

        // invalid
        $taskData->task = 123;

        $this->assertCount(1, $validator->validate($taskData));
    }

    public function testAnnotations()
    {
        // "task" property may only have Type constraint from annotation
        $annotationReader = new AnnotationReader();
        $reflectionProperty = new \ReflectionProperty(TaskData::class, 'task');
        $propertyAnnotations = $annotationReader->getPropertyAnnotations($reflectionProperty);
        $this->assertCount(1, $propertyAnnotations);
        $this->assertContainsOnlyInstancesOf(Type::class, $propertyAnnotations);

        // dueDate may not have an annotation
        $reflectionProperty = new \ReflectionProperty(TaskData::class, 'dueDate');
        $propertyAnnotations = $annotationReader->getPropertyAnnotations($reflectionProperty);
        $this->assertCount(0, $propertyAnnotations);
    }
}
