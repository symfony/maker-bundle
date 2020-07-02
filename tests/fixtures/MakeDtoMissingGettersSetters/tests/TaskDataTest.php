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
    public function testSettersGetters()
    {
        // no setters / getters
        $this->assertFalse(method_exists(Task::class, 'setTask'));
        $this->assertFalse(method_exists(Task::class, 'getTask'));
        $this->assertTrue(method_exists(TaskData::class, 'setTask'));
        $this->assertTrue(method_exists(TaskData::class, 'getTask'));

        // missing getter but having setter
        $this->assertTrue(method_exists(Task::class, 'setICanGetNo'));
        $this->assertFalse(method_exists(Task::class, 'getICanGetNo'));
        $this->assertTrue(method_exists(TaskData::class, 'setICanGetNo'));
        $this->assertTrue(method_exists(TaskData::class, 'getICanGetNo'));

        // having getter but missing setter
        $this->assertFalse(method_exists(Task::class, 'setICanSetNo'));
        $this->assertTrue(method_exists(Task::class, 'getICanSetNo'));
        $this->assertTrue(method_exists(TaskData::class, 'setICanSetNo'));
        $this->assertTrue(method_exists(TaskData::class, 'getICanSetNo'));
    }

    public function testConstructorWithMissingGetter()
    {
        $taskEntity = new Task();
        $taskEntity->setICanGetNo('Satisfaction');

        // set private task property of the entity via Reflection - we do not have a setter
        $entityReflection = new \ReflectionClass($taskEntity);
        $taskProperty = $entityReflection->getProperty('task');
        $taskProperty->setAccessible(true);
        $taskProperty->setValue($taskEntity, 'Foo');

        // set the private iCanSetNo property of the entity via Reflection - we do not have a setter
        $entityReflection = new \ReflectionClass($taskEntity);
        $taskProperty = $entityReflection->getProperty('iCanSetNo');
        $taskProperty->setAccessible(true);
        $taskProperty->setValue($taskEntity, 'Satisfaction');

        $taskData = new TaskData($taskEntity);

        $this->assertEquals('Satisfaction', $taskData->getICanSetNo());
        $this->assertNull($taskData->getICanGetNo());
        $this->assertNull($taskData->getTask());
    }

    public function testMutator()
    {
        $taskData = new TaskData;
        $taskData->setTask('Acme');
        $taskData->setICanGetNo('Satisfaction');
        $taskData->setICanSetNo('Bar');

        $taskEntity = new Task();
        // will update the entity including the private properties
        $taskEntity->updateFromTaskData($taskData);

        // make the private task property accessible to compare the values
        $entityReflection = new \ReflectionClass($taskEntity);
        $taskProperty = $entityReflection->getProperty('task');
        $taskProperty->setAccessible(true);
        $this->assertEquals($taskProperty->getValue($taskEntity), 'Acme');

        // make the private iCanGetNo property accessible to compare the values
        $entityReflection = new \ReflectionClass($taskEntity);
        $iCanGetNoProperty = $entityReflection->getProperty('iCanGetNo');
        $iCanGetNoProperty->setAccessible(true);
        $this->assertEquals($iCanGetNoProperty->getValue($taskEntity), 'Satisfaction');

        $this->assertEquals($taskEntity->getICanSetNo(), 'Bar');
    }
}
