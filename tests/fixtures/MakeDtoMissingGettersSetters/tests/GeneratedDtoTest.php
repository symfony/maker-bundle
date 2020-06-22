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

class GeneratedDtoTest extends KernelTestCase
{
    /**
     * Use the helpers to make sure that they work, even though there are missing getters/setters.
     */
    public function testHelpers()
    {
        $taskEntity = new Task();
        $taskEntity->setDueDate(new \DateTime('2018-01-29 01:30'));

        $taskData = new TaskData($taskEntity);

        $this->assertEquals($taskEntity->getDueDate(), $taskData->getDueDate());

        $taskEntity = new Task();
        $taskData->fill($taskEntity);

        $this->assertEquals($taskEntity->getDueDate(), $taskData->getDueDate());
    }
}
