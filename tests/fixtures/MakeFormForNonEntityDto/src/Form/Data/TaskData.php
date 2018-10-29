<?php

/*
 * This file is part of the Symfony MakerBundle package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form\Data;

use App\Entity\Task;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Data transfer object for Task.
 * Add your constraints as annotations to the properties.
 */
class TaskData
{
    /**
     * @Assert\NotBlank()
     */
    public $task;

    public $dueDate;

    /**
     * Create DTO, optionally extracting data from a model.
     *
     * @param Task|null $task
     */
    public function __construct(? Task $task = null)
    {
        if ($task instanceof Task) {
            $this->extract($task);
        }
    }

    /**
     * Fill entity with data from the DTO.
     *
     * @param Task $task
     */
    public function fill(Task $task): Task
    {
        $task
            ->setTask($this->task)
            ->setDueDate($this->dueDate)
        ;

        return $task;
    }

    /**
     * Extract data from entity into the DTO.
     *
     * @param Task $task
     */
    public function extract(Task $task): self
    {
        $this->task = $task->getTask();
        $this->dueDate = $task->getDueDate();

        return $this;
    }
}
