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

use App\Entity\Task;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class TaskDataTest extends KernelTestCase
{
    public function testNoMutators()
    {
        $this->assertFalse(method_exists(Task::class, 'updateFromTaskData'));
    }
}
