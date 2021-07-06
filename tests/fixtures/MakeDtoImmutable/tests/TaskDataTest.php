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
    public function testSetters()
    {
        $this->assertFalse(method_exists(TaskData::class, 'setTask'));
        $this->assertFalse(method_exists(TaskData::class, 'setDueDate'));
    }

    public function testGetters()
    {
        $this->assertTrue(method_exists(TaskData::class, 'getTask'));
        $this->assertTrue(method_exists(TaskData::class, 'getDueDate'));
    }

    public function testConstructor()
    {
        $this->assertTrue(method_exists(TaskData::class, '__construct'));

        $taskData = new TaskData('Acme', new \DateTime('2018-01-29 01:30'));

        $this->assertEquals($taskData->getTask(), 'Acme');
        $this->assertEquals($taskData->getDueDate(), new \DateTime('2018-01-29 01:30'));
    }

    public function testNamedConstructor()
    {
        $this->assertTrue(method_exists(TaskData::class, 'fromTask'));

        $taskEntity = new Task;
        $taskEntity->setTask('Acme');
        $taskEntity->setDueDate(new \DateTime('2018-01-29 01:30'));
        $taskData = TaskData::fromTask($taskEntity);

        $this->assertEquals($taskData->getTask(), 'Acme');
        $this->assertEquals($taskData->getDueDate(), new \DateTime('2018-01-29 01:30'));
    }
}
