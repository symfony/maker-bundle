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
use Symfony\Component\Validator\Constraints\NotBlank;

class TaskDataTest extends KernelTestCase
{
    public function testMappedSuperClass()
    {
        $this->assertClassHasAttribute('task', TaskData::class);
        $this->assertClassHasAttribute('dueDate', TaskData::class);
        $this->assertClassNotHasAttribute('id', TaskData::class);

        $this->assertFalse(method_exists(TaskData::class, 'setTask'));
        $this->assertFalse(method_exists(TaskData::class, 'getTask'));

        $this->assertFalse(method_exists(TaskData::class, 'setDueDate'));
        $this->assertFalse(method_exists(TaskData::class, 'getDueDate'));
    }

    public function testMutator()
    {
        $taskEntity = new Task();
        $taskEntity->setTask('Acme');
        $taskEntity->setDueDate(new \DateTime('2018-01-29 01:30'));

        $taskData = new TaskData($taskEntity);

        $this->assertEquals($taskEntity->getTask(), $taskData->task);
        $this->assertEquals($taskEntity->getDueDate(), $taskData->dueDate);

        $taskData->task = 'Foo';

        $taskEntity = new Task();
        $taskEntity->updateFromTaskData($taskData);

        $this->assertEquals($taskEntity->getTask(), $taskData->task);
        $this->assertEquals($taskEntity->getDueDate(), $taskData->dueDate);
    }
}
