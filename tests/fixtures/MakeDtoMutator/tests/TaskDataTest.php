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
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class TaskDataTest extends KernelTestCase
{
    public function testTaskData()
    {
        $this->assertTrue(method_exists(Task::class, 'updateFromTaskData'));
    }

    public function testMutator()
    {
        $taskData = new TaskData();
        $taskData->dueDate = new \DateTime('2018-01-29 01:30');
        $taskData->task = 'Acme';

        $taskEntity = new Task();
        $taskEntity->updateFromTaskData($taskData);

        $this->assertEquals($taskEntity->getDueDate(), $taskData->dueDate);
        $this->assertEquals($taskEntity->getTask(), $taskData->task);
    }
}
