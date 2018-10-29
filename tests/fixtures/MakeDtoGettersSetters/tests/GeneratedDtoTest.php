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

/**
 * @requires PHP 7.1
 */
class GeneratedDtoTest extends KernelTestCase
{
    public function testGeneratedDto()
    {
        $this->assertTrue(method_exists(TaskData::class, 'setTask'));
        $this->assertTrue(method_exists(TaskData::class, 'getTask'));

        $this->assertTrue(method_exists(TaskData::class, 'setDueDate'));
        $this->assertTrue(method_exists(TaskData::class, 'getDueDate'));
    }

    public function testGettersSetters()
    {
        $taskData = new Task();
        $taskData->setTask('Acme');
        $taskData->setDueDate(new \DateTime('2018-01-29 01:30'));

        $this->assertEquals($taskData->getTask(), 'Acme');
        $this->assertEquals($taskData->getDueDate(), new \DateTime('2018-01-29 01:30'));
    }
}
